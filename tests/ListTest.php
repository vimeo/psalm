<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ListTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'destructuringWithTrailingEmptyEntries' => [
                'code' => '<?php
                    ["a" => $a, ] = ["a" => 1];
                    list("b" => $b, ) = ["b" => 2];
                    [$c, , ] = [3, 4];',
                'assertions' => [
                    '$a===' => '1',
                    '$b===' => '2',
                    '$c===' => '3',
                ],
            ],
            'destructuringInAConditionLeavesOutTheNullOfANullValue' => [
                'code' => '<?php
                    /** @return list{int, int}|null */
                    function pair(int $c): ?array {
                        return $c ? [$c, $c] : null;
                    }

                    /** @return array{x: int}|null */
                    function point(int $c): ?array {
                        return $c ? ["x" => $c] : null;
                    }

                    if ([$a, $b] = pair(1)) {
                        echo $a + $b;
                    }

                    if (["x" => $x] = point(1)) {
                        echo $x + 1;
                    }

                    while ([$c, $d] = pair(rand(0, 1))) {
                        echo $c + $d;
                    }',
            ],
            'keyedDestructuringOfATypedValueIsNotAMixedAssignment' => [
                'code' => '<?php
                    /** @return array{name: string, tags: list<string>} */
                    function row(): array {
                        return ["name" => "x", "tags" => []];
                    }

                    ["name" => $name, "tags" => $tags] = row();

                    /** @var mixed $mixed */
                    $mixed = 1;
                    /** @psalm-suppress MixedAssignment */
                    ["a" => $suppressed] = ["a" => $mixed];

                    echo $name . implode(",", $tags);',
            ],
            'destructureAKeyFromEachTypeOfAUnion' => [
                'code' => '<?php
                    /** @return array{author: int}|ArrayObject<string, string> */
                    function getComment() {
                        return ["author" => 1];
                    }

                    ["author" => $author] = getComment();',
                'assertions' => [
                    '$author' => 'int|string',
                ],
            ],
            'destructureAKeyFromTheFallbackOfAShape' => [
                'code' => '<?php
                    /** @return array{author: int, ...<string, string>} */
                    function getComment(): array {
                        return ["author" => 1];
                    }

                    ["author" => $author, "text" => $text] = getComment();',
                'assertions' => [
                    '$author' => 'int',
                    '$text' => 'string',
                ],
            ],
            'simpleVars' => [
                'code' => '<?php
                    list($a, $b) = ["a", "b"];',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'string',
                ],
            ],
            'simpleVarsWithSeparateTypes' => [
                'code' => '<?php
                    list($a, $b) = ["a", 2];',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'simpleVarsWithSeparateTypesInVar' => [
                'code' => '<?php
                    $bar = ["a", 2];
                    list($a, $b) = $bar;',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'thisVar' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $a = "";

                        /** @var string */
                        public $b = "";

                        public function fooFoo(): string
                        {
                            list($this->a, $this->b) = ["a", "b"];

                            return $this->a;
                        }
                    }',
            ],
            'mixedNestedAssignment' => [
                'code' => '<?php
                    /** @psalm-suppress MissingReturnType */
                    function getMixed() {}

                    /**
                     * @psalm-suppress MixedArrayAccess
                     * @psalm-suppress MixedAssignment
                     */
                    list($a, list($b, $c)) = getMixed();',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'mixed',
                    '$c' => 'mixed',
                ],
            ],
            'explicitLiteralKey' => [
                'code' => '<?php
                    /** @param list<int> $a */
                    function takesList($a): void {}

                    $a = [1, 1 => 2, 3];
                    takesList($a);',
            ],
            'simpleTypeInfererNonEmptyList' => [
                'code' => '<?php

                    class Foo {
                        public const VARS = [
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "x",
                            "y",
                        ];
                    }

                    /** @param list<string> $vars */
                    function foo(array $vars): void {
                        print_r($vars);
                    }

                    foo(Foo::VARS);
                    ',
            ],
            'destructuringIntoOffsetsOfUndefinedVariable' => [
                'code' => '<?php
                    if (!isset($ticket[\'custid\']) || !$ticket[\'custid\']) {
                        [$ticket[\'requestor\'], $ticket[\'requestor_mail\']] = sscanf($ticket[\'req\'], "%[^<]<%[^>]");
                    } else {
                        [$ticket[\'requestor_mail\']] = sscanf($ticket[\'req\'], "<%[^>]");
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'PossiblyUndefinedGlobalVariable',
                    'UndefinedGlobalVariable',
                    'PossiblyUndefinedArrayOffset',
                    'PossiblyNullArrayAccess',
                    'MixedArrayAccess',
                    'MixedArrayAssignment',
                    'MixedAssignment',
                    'PossiblyInvalidArrayAccess',
                    'InvalidArrayOffset',
                    'MixedArgument',
                ],
            ],
            'destructuringIntoOffsetsOfUndefinedVariableWithUserFunction' => [
                'code' => '<?php
                    /** @return list{string, string}|null */
                    function parse(string $s) { return null; }

                    if (!isset($t[\'id\'])) {
                        [$t[\'a\'], $t[\'b\']] = parse(\'x\');
                    } else {
                        [$t[\'b\']] = parse(\'x\');
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'PossiblyUndefinedGlobalVariable',
                    'UndefinedGlobalVariable',
                    'PossiblyUndefinedArrayOffset',
                    'PossiblyNullArrayAccess',
                    'PossiblyInvalidArrayAccess',
                ],
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'destructuringWithKeyedAndUnkeyedEntries' => [
                'code' => '<?php
                    /** @param array<int, float> $source */
                    function f(array $source): float {
                        [0 => $a, $b] = $source;
                        return $a + $b;
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Cannot mix keyed and unkeyed array entries in assignments',
            ],
            'destructuringWithAnEmptyKeyedEntry' => [
                'code' => '<?php
                    /** @param array<int, float> $source */
                    function f(array $source): float {
                        [0 => $a, , 2 => $b] = $source;
                        return $a + $b;
                    }',
                'error_message' => 'Cannot use empty array entries in keyed array assignment',
            ],
            'destructuringWithAnEmptyKeyedEntryBeforeATrailingComma' => [
                'code' => '<?php
                    ["a" => $a, , ] = ["a" => 1];',
                'error_message' => 'Cannot use empty array entries in keyed array assignment',
            ],
            'listWithAnEmptyKeyedEntryBeforeATrailingComma' => [
                'code' => '<?php
                    list("a" => $a, , ) = ["a" => 1];',
                'error_message' => 'Cannot use empty array entries in keyed array assignment',
            ],
            'destructuringIntoAnEmptyList' => [
                'code' => '<?php
                    [, ] = [1];',
                'error_message' => 'Cannot use empty list',
            ],
            'destructuringWithASpread' => [
                'code' => '<?php
                    [...$a] = [1];',
                'error_message' => 'Spread operator is not supported in assignments',
            ],
            'foreachDestructuringWithKeyedAndUnkeyedEntries' => [
                'code' => '<?php
                    foreach ([[1, 2]] as [0 => $a, $b]) {
                        echo $a + $b;
                    }',
                'error_message' => 'Cannot mix keyed and unkeyed array entries in assignments',
            ],
            'destructuringAKeyMissingFromASealedShape' => [
                'code' => '<?php
                    $source = ["a" => 1, "b" => 2];
                    ["a" => $a, "c" => $c] = $source;',
                'error_message' => "InvalidArrayOffset - src" . DIRECTORY_SEPARATOR . "somefile.php:3:40 - Cannot access value with offset",
            ],
            'destructuringAnEmptyArray' => [
                'code' => '<?php
                    /** @return array{} */
                    function none(): array {
                        return [];
                    }

                    [$p] = none();',
                'error_message' => "InvalidArrayOffset - src" . DIRECTORY_SEPARATOR . "somefile.php:7:22 - Cannot access value with offset 0",
            ],
            'destructuringAKeyOfAnEmptyArray' => [
                'code' => '<?php
                    $e = [];
                    ["a" => $p] = $e;',
                'error_message' => "InvalidArrayOffset - src" . DIRECTORY_SEPARATOR . "somefile.php:3:29 - Cannot access value with offset",
            ],
            'destructuringAPositionMissingFromSomeOfTheTypes' => [
                'code' => '<?php
                    /** @param list{int, int}|ArrayObject<int, int> $source */
                    function f($source): void {
                        [$a, $b, $c] = $source;
                        echo $a, $b, $c;
                    }',
                'error_message' => 'PossiblyInvalidArrayOffset - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:34 - Cannot access value with offset 2',
            ],
            'destructuringAKeyMissingFromSomeOfTheTypes' => [
                'code' => '<?php
                    /** @param array{a: int}|ArrayObject<string, int> $source */
                    function f($source): void {
                        ["c" => $c] = $source;
                        echo $c;
                    }',
                'error_message' => "PossiblyInvalidArrayOffset - src" . DIRECTORY_SEPARATOR . "somefile.php:4:33 - Cannot access value with offset",
            ],
            'destructuringInAConditionKeepsTheNullOfANullableItem' => [
                'code' => '<?php
                    /** @return list{?int, int}|null */
                    function pair(int $c): ?array {
                        return $c ? [null, $c] : null;
                    }

                    if ([$a, $b] = pair(1)) {
                        echo $a + $b;
                    }',
                'error_message' => 'PossiblyNullOperand - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:30 - Left operand',
            ],
            'keyedDestructuringOfAMixedValueIsAMixedAssignment' => [
                'code' => '<?php
                    /** @return array{name: mixed} */
                    function row(): array {
                        return ["name" => "x"];
                    }

                    ["name" => $name] = row();

                    echo strlen((string) $name);',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:32 - Unable to determine the type that $name is being assigned to',
            ],
            'keyedDestructuringOfAMixedValueFromAMapIsAMixedAssignment' => [
                'code' => '<?php
                    /** @return array<string, mixed> */
                    function row(): array {
                        return [];
                    }

                    ["name" => $name] = row();

                    echo strlen((string) $name);',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:32 - Unable to determine the type that $name is being assigned to',
            ],
            'thisVarWithBadType' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 0;

                        /** @var string */
                        public $b = "";

                        public function fooFoo(): string
                        {
                            list($this->a, $this->b) = ["a", "b"];

                            return $this->a;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:11',
            ],
            'explicitVariableKey' => [
                'code' => '<?php
                    /** @param list<int> $a */
                    function takesList($a): void {}

                    /** @return array-key */
                    function getKey() {
                        return 0;
                    }

                    $a = [getKey() => 1];
                    takesList($a);',
                'error_message' => 'ArgumentTypeCoercion',
            ],
        ];
    }
}
