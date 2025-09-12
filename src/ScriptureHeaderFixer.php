<?php

declare(strict_types=1);

/**
 * ©️ copyright 2025 - johninamillion
 * 🙏🏻 In the beginning God created the heaven and the earth. - Genesis 1:1, KJV
 */

namespace johninamillion\ScriptureHeader;

use JsonException;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * Scripture Header Fixer class
 *
 * @template TFixerInputConfig of array<string, mixed>
 * @template TFixerComputedConfig of array<string, mixed>
 * @implements ConfigurableFixerInterface<TFixerInputConfig, TFixerComputedConfig>
 *
 * @package johninamillion\ScriptureHeaderFixer
 * @since 0.1.0
 */
class ScriptureHeaderFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    public const DEFAULT_BIBLE = __DIR__ . '/../data/KJV.json';
    public const DEFAULT_COPYRIGHT = __DIR__ . '/../copyright.php';
    public const DEFAULT_COPYRIGHT_PATTERN = '/^\/\*\*[\s\S]+?copyright (\d{4}) - .+$/';

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
     * Pattern to compare copyright.
     *
     * @access protected
     * @var string
     */
    protected string $copyrightPattern = '';

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
     * @param TFixerInputConfig $configuration
     * @return void
     */
    public function configure(array $configuration): void
    {
        $this->author = $configuration['author'] ?? $this->getComposerAuthor();
        $this->biblePath = $configuration['bible'] ?? self::DEFAULT_BIBLE;
        $this->copyrightPath = $configuration['template'] ?? self::DEFAULT_COPYRIGHT;
        $this->copyrightPattern = $configuration['pattern'] ?? self::DEFAULT_COPYRIGHT_PATTERN;
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
            ->setDefault(self::DEFAULT_COPYRIGHT);

        $patternBuilder = new FixerOptionBuilder('pattern', 'Regex pattern to identify existing copyright header');
        $patternBuilder
            ->setAllowedTypes(['string'])
            ->setDefault(self::DEFAULT_COPYRIGHT_PATTERN);

        return new FixerConfigurationResolver([
            $authorBuilder->getOption(),
            $bibleBuilder->getOption(),
            $templateBuilder->getOption(),
            $patternBuilder->getOption(),
        ]);
    }

    /**
     * Get the author from the composer.json file.
     *
     * @access protected
     * @return string
     */
    public function getComposerAuthor(): string
    {
        if (
            !file_exists(($path = getcwd() . '/composer.json'))
            || !($json = file_get_contents($path))
        ) {

            return '';
        }

        // try to extract the author from the composer.json file
        try {
            /** @var array<string,mixed> $pkg */
            $pkg = json_decode($json, true, 128, JSON_THROW_ON_ERROR);

            if (!isset($pkg['name'])) {
                throw new JsonException('Invalid JSON format. Expected "name" key.');
            }

            if (!is_string($pkg['name'])) {
                throw new JsonException('Invalid JSON format. Expected "name" key to be a string.');
            }

            return explode("/", $pkg['name'])[0];
        } // return the exception message if JSON is invalid
        catch (JsonException $e) {
            return $e->getMessage();
        }
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

        return 'MillionVisions/scripture_header';
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
     * Returns true if this fixer supports the file.
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
        $copyright = $this->getCopyright();

        $newIndex = $this->findScriptureHeaderInsertionIndex($tokens, 'after_declare_strict');
        $currentIndex = $this->findScriptureHeaderCurrentIndex($tokens, $newIndex - 1);

        if ($currentIndex === null) {
            $this->insertScriptureHeader($tokens, $newIndex);
            return;
        }

        $currentContent = $tokens[$currentIndex]->getContent();

        if (!$this->compareWithPattern($currentContent, $copyright)) {
            $this->removeScriptureHeader($tokens, $currentIndex);
            $this->insertScriptureHeader($tokens, $newIndex);
        }
    }

    /**
     * Compare the current header content with the expected copyright content using regex.
     *
     * @param string $currentHeader
     * @param string $newHeader
     * @return bool
     */
    protected function compareWithPattern(string $currentHeader, string $newHeader): bool
    {
        $currentHeaderStaticPart = Preg::replace($this->copyrightPattern, '', $currentHeader);
        $newHeaderStaticPart = Preg::replace($this->copyrightPattern, '', $newHeader);

        return $currentHeaderStaticPart === $newHeaderStaticPart;
    }

    /**
     * Find the current index of the scripture header in the tokens.
     *
     * @param Tokens $tokens
     * @param int $headerNewIndex
     * @return int|null
     */
    protected function findScriptureHeaderCurrentIndex(Tokens $tokens, int $headerNewIndex): ?int
    {
        $copyright = $this->getCopyright();
        $index = $tokens->getNextNonWhitespace($headerNewIndex);

        if ($index === null || !$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {

            return null;
        }

        $next = $index + 1;

        if (!isset($tokens[$next]) || !$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {

            return $index;
        }

        if ($tokens[$next]->isWhitespace()) {
            if (!Preg::match('/^\h*\R\h*$/D', $tokens[$next]->getContent())) {

                return $index;
            }

            $next++;
        }

        if (!isset($tokens[$next]) || (!$tokens[$next]->isClassy() && !$tokens[$next]->isGivenKind(T_FUNCTION))) {

            return $index;
        }

        if ($copyright === $tokens[$next]->getContent()) {

            return $index;
        }

        return null;
    }

    /**
     * Find the index where the scripture header should be inserted.
     *
     * @param Tokens $tokens
     * @param string $location
     * @return int
     */
    protected function findScriptureHeaderInsertionIndex(Tokens $tokens, string $location): int
    {
        $openTagIndex = $tokens[0]->isGivenKind(T_INLINE_HTML) ? 1 : 0;

        if ('after_open' === $location) {

            return $openTagIndex + 1;
        }

        $index = $tokens->getNextMeaningfulToken($openTagIndex);

        if (null === $index) {

            return $openTagIndex + 1; // file without meaningful tokens but an open tag, comment should always be placed directly after the open tag
        }

        if (!$tokens[$index]->isGivenKind(T_DECLARE)) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($index);

        if (null === $next || !$tokens[$next]->equals('(')) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals([T_STRING, 'strict_types'], false)) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals('=')) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->isGivenKind(T_LNUMBER)) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals(')')) {

            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);

        if (null === $next || !$tokens[$next]->equals(';')) { // don't insert after close tag

            return $openTagIndex + 1;
        }

        return $next + 1;
    }

    /**
     * Fix the whitespace around the scripture header.
     *
     * @param Tokens $tokens
     * @param int $index
     * @return void
     */
    protected function fixWhiteSpaceAroundScriptureHeader(Tokens $tokens, int $index): void
    {
        $lineEnding = "\n";

        $expectedLineCount = $tokens->getNextMeaningfulToken($index) !== null
            ? 2
            : 1;

        if ($index === count($tokens) - 1) {
            $tokens->insertAt($index + 1, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount)]));
        } else {
            $lineBreakCount = $this->getLineBreakCount($tokens, $index, 1);

            if ($lineBreakCount < $expectedLineCount) {
                $missing = str_repeat($lineEnding, $expectedLineCount - $lineBreakCount);

                if ($tokens[$index + 1]->isWhitespace()) {
                    $tokens[$index + 1] = new Token([\T_WHITESPACE, $missing . $tokens[$index + 1]->getContent()]);
                } else {
                    $tokens->insertAt($index + 1, new Token([\T_WHITESPACE, $missing]));
                }
            } elseif ($lineBreakCount > $expectedLineCount && $tokens[$index + 1]->isWhitespace()) {
                $newLinesToRemove = $lineBreakCount - $expectedLineCount;
                $tokens[$index + 1] = new Token([
                    \T_WHITESPACE,
                    Preg::replace("/^\\R{{$newLinesToRemove}}/", '', $tokens[$index + 1]->getContent()),
                ]);
            }
        }

        // fix lines before header comment
        $expectedLineCount = 2;
        $prev = $tokens->getPrevNonWhitespace($index);

        $regex = '/\h$/';

        if ($prev === null) {
            return;
        }

        if ($tokens[$prev]->isGivenKind(\T_OPEN_TAG) && Preg::match($regex, $tokens[$prev]->getContent())) {
            $tokens[$prev] = new Token([\T_OPEN_TAG, Preg::replace($regex, $lineEnding, $tokens[$prev]->getContent())]);
        }

        $lineBreakCount = $this->getLineBreakCount($tokens, $index, -1);

        if ($lineBreakCount < $expectedLineCount) {
            // because of the way the insert index was determined for header comment there cannot be an empty token here
            $tokens->insertAt($index, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount - $lineBreakCount)]));
        }
    }

    /**
     * Get the copyright header content.
     *
     * @return string
     */
    protected function getCopyright(): string
    {
        try {
            // load verses and pick random
            $this->loadVerses();
        } catch (JsonException $e) {
            return $e->getMessage();
        }

        extract([
            'author' => $this->author,
            'verse' => $this->verses[array_rand($this->verses)],
            'year' => date('Y'),
        ]);

        /** @var string $copyright */
        $copyright = include $this->copyrightPath;

        return $copyright;
    }

    /**
     * Get the number of line breaks in the whitespace around the specified index.
     *
     * @param Tokens $tokens
     * @param int $index
     * @param int $direction
     * @return int
     */
    protected function getLineBreakCount(Tokens $tokens, int $index, int $direction): int
    {
        $whitespace = '';
        $i = $index;
        $d = $direction;

        for ($i += $d; isset($tokens[$i]); $i += $d) {
            $token = $tokens[$i];

            if ($token->isWhitespace()) {
                $whitespace .= $token->getContent();

                continue;
            }

            if (-1 === $direction && $token->isGivenKind(T_OPEN_TAG)) {
                $whitespace .= $token->getContent();
            }

            if ('' !== $token->getContent()) {
                break;
            }
        }

        return substr_count($whitespace, "\n");
    }

    /**
     * Insert the scripture header at the specified index.
     *
     * @param Tokens $tokens
     * @param int $index
     */
    protected function insertScriptureHeader(Tokens $tokens, int $index): void
    {
        $copyright = $this->getCopyright();

        $tokens->insertAt(
            $index,
            new Token([T_DOC_COMMENT, $copyright])
        );

        $this->fixWhiteSpaceAroundScriptureHeader($tokens, $index);
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

        // try to load verses from JSON
        try {
            $json = file_get_contents($path);

            if (!is_string($json)) {
                throw new JsonException('Failed to read JSON file.');
            }

            $bible = json_decode($json, true, 128, JSON_THROW_ON_ERROR);

            if (!is_array($bible) || !isset($bible['books'])) {
                throw new JsonException('Invalid JSON format. Expected "books" key.');
            }

            /** @var string $version */
            $version = pathinfo($path, PATHINFO_FILENAME);

            foreach ($bible['books'] as $book) {

                if (!is_array($book) || !isset($book['chapters'])) {
                    throw new JsonException('Invalid JSON format. Expected "chapters" key.');
                }

                foreach ($book['chapters'] as $chapter) {

                    if (!is_array($chapter) || !isset($chapter['verses'])) {
                        throw new JsonException('Invalid JSON format. Expected "verses" key.');
                    }

                    foreach ($chapter['verses'] as $verse) {
                        $this->verses[] = "{$verse['text']} - {$verse['name']}, {$version}";
                    }
                }
            }
        }
        // failed to load verses from JSON
        catch (JsonException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    /**
     * Remove the scripture header at the specified index.
     *
     * @param Tokens $tokens
     * @param int $index
     */
    private function removeScriptureHeader(Tokens $tokens, int $index): void
    {
        $prevIndex = $index - 1;
        $prevToken = $tokens[$prevIndex];
        $newlineRemoved = false;

        if ($prevToken->isWhitespace()) {
            $content = $prevToken->getContent();

            if (Preg::match('/\R/', $content)) {
                $newlineRemoved = true;
            }

            $content = Preg::replace('/\R?\h*$/', '', $content);

            $tokens->ensureWhitespaceAtIndex($prevIndex, 0, $content);
        }

        $nextIndex = $index + 1;
        $nextToken = $tokens[$nextIndex] ?? null;

        if (!$newlineRemoved && null !== $nextToken && $nextToken->isWhitespace()) {
            $content = Preg::replace('/^\R/', '', $nextToken->getContent());

            $tokens->ensureWhitespaceAtIndex($nextIndex, 0, $content);
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }
}
