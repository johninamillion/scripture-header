<?php

namespace johninamillion\ScriptureHeader;

use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * Scripture Header Fixer class
 *
 * @package johninamillion\ScriptureHeaderFixer
 * @since 0.1.0
 */
class ScriptureHeaderFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    const DEFAULT_BIBLE = __DIR__ . '/../data/bible-kjv.json';
    const DEFAULT_TEMPLATE = __DIR__ . '/../copyright.php';

    /**
     * Author of the copyright header.
     *
     * @access protected
     * @var string
     */
    protected string $author;

    /**
     * Path to the JSON file containing the Bible verses.
     *
     * @access protected
     * @var string
     */
    protected string $biblePath;

    /**
     * Path to the copyright template file.
     *
     * @access protected
     * @var string
     */
    protected string $copyrightPath;

    /**
     * In-memory cache of all verses
     *
     * @access protected
     * @var string[]
     */
    protected array $verses = [];

    /**
     * Set configuration.
     * {@inheritdoc}
     *
     * @access public
     * @param array $configuration
     * @return void
     */
    public function configure(array $configuration): void
    {
        $this->author = $configuration['author'] ?? $this->getComposerAuthor();
        $this->biblePath = $configuration['bible'] ?? self::DEFAULT_BIBLE;
        $this->copyrightPath = $configuration['template'] ?? self::DEFAULT_TEMPLATE;
    }

    /**
     * Defines the available configuration options of the fixer.
     *
     * @access public
     * @return FixerConfigurationResolverInterface
     */
    public function getConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        $authorBuilder = new FixerOptionBuilder('author', 'Name or company to appear in the copyright line');
        $authorBuilder
            ->setAllowedTypes(['string'])
            ->setDefault($this->getComposerAuthor());

        $bibleBuilder = new FixerOptionBuilder('bible', 'Path to the JSON file with Bible verses (sources: https://github.com/scrollmapper/bible_databases)');
        $bibleBuilder
            ->setAllowedTypes(['string'])
            ->setDefault(self::DEFAULT_BIBLE);

        $templateBuilder = new FixerOptionBuilder('template', 'Path to a PHP file returning the copyright for the header');
        $templateBuilder
            ->setAllowedTypes(['string'])
            ->setDefault(self::DEFAULT_TEMPLATE);

        return new FixerConfigurationResolver([
            $authorBuilder->getOption(),
            $bibleBuilder->getOption(),
            $templateBuilder->getOption()
        ]);
    }

    /**
     * Get the author from the composer.json file.
     *
     * @access protected
     * @return string|null
     */
    public function getComposerAuthor(): ?string
    {
        if (
            !file_exists(($path = getcwd() . '/composer.json'))
            || false === ($json = file_get_contents($path))
        ) {

            return '';
        }

        $pkg = json_decode($json, true);

        return explode("/", $pkg['name'])[0] ?? '';
    }

    /**
     * Get the definition of the fixer.
     *
     * @access protected
     * @return FixerDefinitionInterface
     */
    public function getDefinition(): FixerDefinitionInterface
    {

        return new FixerDefinition(
            'Adds your copyright header with a random bible verse to the top of the file.',
            []
        );
    }

    /**
     * Get the name of the fixer.
     *
     * @access public
     * @return string
     */
    public function getName(): string
    {

        return 'scripture_header';
    }

    /**
     * Get the priority of the fixer.
     *
     * @access public
     * @return int
     */
    public function getPriority(): int
    {

        return 0;
    }

    /**
     * Check if the fixer is a candidate for the given tokens.
     *
     * @access protected
     * @param Tokens $tokens
     * @return bool
     */
    public function isCandidate(Tokens $tokens): bool
    {

        return $tokens->isAnyTokenKindsFound([T_OPEN_TAG]);
    }

    /**
     * Specifies whether the fixer is risky or not.
     *
     * @access public
     * @return bool
     */
    public function isRisky(): bool
    {

        return false;
    }

    /**
     * Returns true if the file is supported by this fixer.
     *
     * @access public
     * @param SplFileInfo $file
     * @return bool
     */
    public function supports(SplFileInfo $file): bool
    {

        return pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php';
    }

    /**
     * Apply the fixer to the given file.
     *
     * @access protected
     * @param SplFileInfo $file
     * @param Tokens $tokens
     * @return void
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        // only proceed if file starts with <?php
        if (!$tokens[0]->isGivenKind(T_OPEN_TAG)) {
            return;
        }

        $index = 1;
        $eols = 1;

        // check if there is an space after the open tag
        if ($tokens[1]->isWhitespace()) {
            $index = 2;
            $eols = 0;
        }

        // determine insertion index (after open tag or declare)
        $firstMeaningful = $tokens->getNextMeaningfulToken(0);
        if ($tokens[$firstMeaningful]->isGivenKind(T_DECLARE)) {
            $semicolon = $tokens->getNextTokenOfKind($firstMeaningful, [';']);
            if (null !== $semicolon) {
                $index = $tokens->getNextMeaningfulToken($semicolon);
                $eols = 0;
            }
        }

        // prevent duplicate header
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        if (null !== $prevIndex && $tokens[$prevIndex]->isGivenKind(T_DOC_COMMENT)) {
            return;
        }

        // load verses and pick random
        $this->loadVerses();

        extract([
            'author' => $this->author,
            'verse' => $this->verses[array_rand($this->verses)],
            'year' => date('Y'),
        ]);
        $copyright = include $this->copyrightPath;

        // insert whitespace and doc comment tokens
        if ($eols > 0) {
            $tokens->insertAt($index, new Token([T_WHITESPACE, str_repeat(PHP_EOL, $eols)]));
        }
        $tokens->insertAt(
            $eols > 0 ? $index + 1 : $index,
            new Token([T_DOC_COMMENT, $copyright . PHP_EOL])
        );
    }

    /**
     * Load all verses from the WEB JSON dump into $this->verses.
     *
     * @access protected
     * @return void
     */
    protected function loadVerses(): void
    {
        // return early if verses are already loaded
        if (!empty($this->verses)) {

            return;
        }

        $path = $this->biblePath;

        // return early if the file does not exist or is not readable
        if (!is_file($path) || !is_readable($path)) {

            return;
        }

        /** @var string $json */
        $json = file_get_contents($path);
        /** @var array $bible */
        $bible = json_decode($json, true);

        foreach ($bible['books'] as $book) {
            foreach ($book['chapters'] as $chapter) {
                foreach ($chapter['verses'] as $verse) {
                    $this->verses[] = "{$verse['text']} - {$verse['name']}, KJV";
                }
            }
        }
    }
}