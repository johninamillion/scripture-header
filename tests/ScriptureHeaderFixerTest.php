<?php

namespace johninamillion\ScriptureHeader\Tests;

use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use johninamillion\ScriptureHeader\ScriptureHeaderFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Scripture Header Fixer Test
 *
 * @package johninamillion\ScriptureHeaderFixer\Tests
 * @since 0.1.0
 */
class ScriptureHeaderFixerTest extends TestCase
{
    protected function getCopyright(): string
    {
        $fixer = new ScriptureHeaderFixer();
        $reflection = new ReflectionClass($fixer);

        try {
            $invoke = $reflection
                ->getMethod('getCopyright')
                ->invoke($fixer);

            return is_string($invoke) ? $invoke : '';
        }
        catch (ReflectionException $e) {
            echo $e->getMessage();
            exit();
        }
    }

    /** @test */
    #[Test]
    public function configuration_definition_contains_author_bible_and_template_options(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $resolver = $fixer->getConfigurationDefinition();

        $options = array_map(
            static fn($opt) => $opt->getName(),
            iterator_to_array($resolver->getOptions())
        );

        $this->assertContains('author', $options);
        $this->assertContains('bible', $options);
        $this->assertContains('template', $options);
    }

    /** @test */
    #[Test]
    public function defaults_are_taken_when_no_configuration_given(): void
    {
        $fixer = new class extends ScriptureHeaderFixer {
            public function getComposerAuthor(): string
            {

                return 'johninamillion';
            }

            public function exposeAuthor(): string
            {
                return $this->author;
            }

            public function exposeBiblePath(): string
            {
                return $this->biblePath;
            }

            public function exposeTemplatePath(): string
            {
                return $this->copyrightPath;
            }
        };

        $resolver = $fixer->getConfigurationDefinition();
        /** @var array<string,string> $resolved */
        $resolved = $resolver->resolve([]);
        $fixer->configure($resolved);

        $this->assertSame('johninamillion', $fixer->exposeAuthor());
        $this->assertSame(ScriptureHeaderFixer::DEFAULT_BIBLE, $fixer->exposeBiblePath());
        $this->assertSame(ScriptureHeaderFixer::DEFAULT_COPYRIGHT, $fixer->exposeTemplatePath());
    }

    /** @test */
    #[Test]
    public function custom_configuration_overrides_defaults(): void
    {
        $fixer = new class extends ScriptureHeaderFixer {
            public function getComposerAuthor(): string
            {

                return 'should-not-use-this';
            }

            public function exposeAuthor(): string
            {

                return $this->author;
            }

            public function exposeBiblePath(): string
            {

                return $this->biblePath;
            }

            public function exposeTemplatePath(): string
            {

                return $this->copyrightPath;
            }
        };

        $custom = [
            'author' => 'johninamillion',
            'bible' => '/foo/custom-bible.json',
            'template' => '/foo/custom-template.php',
        ];

        $resolver = $fixer->getConfigurationDefinition();
        /** @var array<string,string> $resolved */
        $resolved = $resolver->resolve($custom);
        $fixer->configure($resolved);

        $this->assertSame('johninamillion', $fixer->exposeAuthor());
        $this->assertSame('/foo/custom-bible.json', $fixer->exposeBiblePath());
        $this->assertSame('/foo/custom-template.php', $fixer->exposeTemplatePath());
    }

    /** @test */
    #[Test]
    public function adds_header_when_php_tag_present(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use the fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php\n\n/**", $output);
        $this->assertStringContainsString('copyright', $output);
    }

    /** @test */
    #[Test]
    public function adds_header_when_php_tag_present_with_spacing(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php \n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use the fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php\n\n/**", $output);
        $this->assertStringContainsString('copyright', $output);
    }

    /** @test */
    #[Test]
    public function adds_header_when_declare_strict_types_present(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php declare(strict_types=1);\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use the fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php declare(strict_types=1);\n\n/**", $output);
        $this->assertStringContainsString('copyright', $output);
    }

    /** @test */
    #[Test]
    public function adds_header_when_declare_strict_types_present_with_spacing(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php declare(strict_types=1); \n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use the fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php declare(strict_types=1);\n\n/**", $output);
        $this->assertStringContainsString('copyright', $output);
    }

    /** @test */
    #[Test]
    public function does_not_add_duplicate_header_when_run_twice(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        $fixer->fix(new SplFileInfo('test.php'), $tokens);
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $code = $tokens->generateCode();

        $this->assertSame(1, substr_count($code, '/**'));
    }

    /** @test */
    #[Test]
    public function does_nothing_when_no_php_tag_present(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "// just a comment\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        $fixer->fix(new SplFileInfo('dummy.php'), $tokens);

        $this->assertSame($input, $tokens->generateCode());
    }

    #[Test]
    public function overwrites_outdated_headers(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $copyright = $this->getCopyright();
        $outdatedCopyright = str_replace(date('Y'), '2020', $copyright);
        $input = "<?php\n{$outdatedCopyright}";
        $tokens = Tokens::fromCode($input);

        // use the fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php\n\n/**", $output);
        $this->assertStringContainsString('copyright', $output);
        $this->assertStringNotContainsString($outdatedCopyright, $output);
    }
}
