<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Exception\CodeException;

class UnresolvableIncludeTest extends TestCase
{
    /**
     * @dataProvider providerUnresolvableInclude
     */
    public function testShouldThrowUnresolvableInclude(string $phpCode, int $expectedExceptionLine): void
    {
        $this->addFile('somefile.php', $phpCode);
        $context = new Context();

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage("UnresolvableInclude - somefile.php:$expectedExceptionLine:");

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return array<string,array{code:string,expectedExceptionLine:int}>
     */
    public function providerUnresolvableInclude(): array
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
        ];
    }

    /**
     * @dataProvider providerNoUnresolvableInclude
     */
    public function testShouldNotThrowUnresolvableInclude(string $phpCode): void
    {
        $this->addFile('somefile.php', $phpCode);

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return array<string,array{code:string}>
     */
    public function providerNoUnresolvableInclude(): array
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
