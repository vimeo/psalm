<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Fixme\FixmeAdder;

use function getcwd;

use const DIRECTORY_SEPARATOR;

final class FixmeAdderTest extends TestCase
{
    #[Override]
    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();

        // let issues accumulate in the IssueBuffer instead of throwing
        $config->throw_exception = false;

        return $config;
    }

    /**
     * @dataProvider providerAddFixmes
     */
    public function testAddFixmes(string $input, string $expected_output, int $expected_added): void
    {
        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php';

        $this->addFile($file_path, $input);
        $this->analyzeFile($file_path, new Context(), false);

        $added = FixmeAdder::add($this->project_analyzer);

        $this->assertSame($expected_added, $added);
        $this->assertSame($expected_output, $this->file_provider->getContents($file_path));
    }

    /**
     * @return array<string, array{input: string, expected_output: string, expected_added: int}>
     */
    public function providerAddFixmes(): array
    {
        return [
            'addsSingleLineDocblockToUndocumentedStatement' => [
                'input' => '<?php
function foo(): void {
    unknown_function_call();
}
',
                'expected_output' => '<?php
function foo(): void {
    /** @psalm-fixme UndefinedFunction */
    unknown_function_call();
}
',
                'expected_added' => 1,
            ],
            'splicesIntoExistingMultilineDocblock' => [
                'input' => '<?php
function foo(): void {
    /**
     * a pre-existing comment
     */
    unknown_function_call();
}
',
                'expected_output' => '<?php
function foo(): void {
    /**
     * a pre-existing comment
     * @psalm-fixme UndefinedFunction
     */
    unknown_function_call();
}
',
                'expected_added' => 1,
            ],
            'expandsExistingSingleLineDocblock' => [
                'input' => '<?php
function foo(): void {
    /** a pre-existing comment */
    unknown_function_call();
}
',
                'expected_output' => '<?php
function foo(): void {
    /**
     * a pre-existing comment
     * @psalm-fixme UndefinedFunction
     */
    unknown_function_call();
}
',
                'expected_added' => 1,
            ],
            'combinesMultipleIssuesOnOneLine' => [
                'input' => '<?php
function foo(): void {
    unknown_function_call($undefined_variable);
}
',
                'expected_output' => '<?php
function foo(): void {
    /** @psalm-fixme PossiblyUndefinedVariable, UndefinedFunction */
    unknown_function_call($undefined_variable);
}
',
                'expected_added' => 2,
            ],
            'doesNothingWhenIssueAlreadySuppressed' => [
                'input' => '<?php
function foo(): void {
    /** @psalm-suppress UndefinedFunction */
    unknown_function_call();
}
',
                'expected_output' => '<?php
function foo(): void {
    /** @psalm-suppress UndefinedFunction */
    unknown_function_call();
}
',
                'expected_added' => 0,
            ],
        ];
    }
}
