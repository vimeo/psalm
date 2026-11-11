<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Exception\CodeException;

final class UnresolvableIncludeTest extends TestCase
{
    #[DataProvider('providerUnresolvableInclude')]
    public function testShouldThrowUnresolvableInclude(string $code, int $expectedExceptionLine): void
    {
        $this->addFile('somefile.php', $code);
        $context = new Context();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage("UnresolvableInclude - somefile.php:$expectedExceptionLine:");

        $this->analyzeFile('somefile.php', $context);
    }


    /**
     * @return array<string,array{code:string,expectedExceptionLine:int}>
     * @psalm-pure
     */
    public static function providerUnresolvableInclude(): array
    {
        return [
            'basic' => [
                'code' => '<?php
                    function requireFile(string $s) : void {
                        require_once($s);
                    }
                ',
                'expectedExceptionLine' => 3,
            ],
            'file_exists_negated_in_an_and_condition' => [
                'code' => '<?php
                    function requireFile(string $s, bool $flag) : void {
                        if ($flag && !file_exists($s)) {
                            require_once($s);
                        }
                    }
                ',
                'expectedExceptionLine' => 4,
            ],
            'file_exists_in_an_or_condition' => [
                'code' => '<?php
                    function requireFile(string $s, bool $flag) : void {
                        if ($flag || file_exists($s)) {
                            require_once($s);
                        }
                    }
                ',
                'expectedExceptionLine' => 4,
            ],
        ];
    }

    #[DataProvider('providerNoUnresolvableInclude')]
    public function testShouldNotThrowUnresolvableInclude(string $code): void
    {
        $this->addFile('somefile.php', $code);

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }


    /**
     * @return array<string,array{code:string}>
     * @psalm-pure
     */
    public static function providerNoUnresolvableInclude(): array
    {
        return [
            'github_issue_908_file_exists' => [
                'code' => '<?php
                    function requireFile(string $s) : void {
                        if (file_exists($s)) {
                            require_once($s);
                        }
                    }
                ',
            ],
            'github_issue_3241_file_exists_of_a_constant' => [
                'code' => '<?php
                    define("SOME_PATH", (string) getenv("SOME_PATH"));

                    if (is_file(SOME_PATH)) {
                        require_once SOME_PATH;
                    }
                ',
            ],
            'github_issue_3241_file_exists_in_an_and_condition' => [
                'code' => '<?php
                    function requireFile(string $s, bool $flag) : void {
                        if ($flag && file_exists($s)) {
                            require_once($s);
                        }

                        if (is_file($s) && $flag) {
                            require_once($s);
                        }
                    }

                    define("SOME_PATH", (string) getenv("SOME_PATH"));

                    if (defined("SOME_PATH") && SOME_PATH && file_exists(SOME_PATH)) {
                        require_once SOME_PATH;
                    }
                ',
            ],
            'github_issue_4788_is_file' => [
                'code' => '<?php
                    function requireFile(string $s) : void {
                        if (is_file($s)) {
                            require_once($s);
                        }
                    }
                ',
            ],
        ];
    }
}
