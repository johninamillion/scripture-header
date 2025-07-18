<?php

namespace johninamillion\ScriptureHeader\Tests;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use johninamillion\ScriptureHeader\ScriptureHeaderFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\Test;

/**
 * Scripture Header Fixer Test
 *
 * @package johninamillion\ScriptureHeaderFixer\Tests
 * @since 0.1.0
 */
class ScriptureHeaderFixerTest extends TestCase
{
    /** @test */
    #[Test]
    public function adds_header_when_php_tag_present(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php\n\n/**", $output);
        $this->assertStringContainsString('(c) copyright', $output);
    }

    /** @test */
    #[Test]
    public function adds_header_when_php_tag_present_with_spacing(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php \n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php \n\n/**", $output);
        $this->assertStringContainsString('(c) copyright', $output);
    }

    /** @test */
    #[Test]
    public function adds_header_when_declare_strict_types_present(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php declare(strict_types=1);\n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php declare(strict_types=1);\n\n/**", $output);
        $this->assertStringContainsString('(c) copyright', $output);
    }

    /** @test */
    #[Test]
    public function adds_header_when_declare_strict_types_present_with_spacing(): void
    {
        $fixer = new ScriptureHeaderFixer();
        $input = "<?php declare(strict_types=1); \n\n\$foo = 123;\n";
        $tokens = Tokens::fromCode($input);

        // use fix method to apply the fixer, cause applyFix is protected
        $fixer->fix(new SplFileInfo('test.php'), $tokens);

        $output = $tokens->generateCode();

        $this->assertStringStartsWith("<?php declare(strict_types=1); \n\n/**", $output);
        $this->assertStringContainsString('(c) copyright', $output);
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
}