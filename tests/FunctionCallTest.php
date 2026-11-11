<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class FunctionCallTest extends TestCase
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
            'zeroIsANonEmptyLowercaseString' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param non-empty-lowercase-string $s */
                    function takesNonEmptyLowercase(string $s): void {}

                    takesNonEmptyLowercase('0');
                    takesNonEmptyLowercase('a');
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyExistsOfAnUnknownOrDynamicPropertyIsNotReported' => [
                'code' => '<?php
                    class Page {
                        public string $title = "";
                    }

                    function check(Page $page, object $object, string $name): void {
                        if (property_exists($page, "missing")) {
                            echo "dynamic";
                        }

                        if (property_exists($page, $name)) {
                            echo "named";
                        }

                        if (property_exists($object, "title")) {
                            echo "object";
                        }

                        if (property_exists(Page::class, "title") || property_exists("Other", "title")) {
                            echo "class name";
                        }
                    }',
            ],
            'arrayFilterWithAStringCallableNarrowsTheResultOfACall' => [
                'code' => '<?php
                    /** @return list<object|null> */
                    function objects(): array {
                        return [];
                    }

                    /** @return list<int|string|null> */
                    function scalars(): array {
                        return [];
                    }

                    $objects = array_values(array_filter(objects(), "is_object"));
                    $ints = array_filter(scalars(), "is_int");
                    $unchanged = array_filter(objects(), "boolval");',
                'assertions' => [
                    '$objects===' => 'list<object>',
                    '$ints===' => 'array<int<0, max>, int>',
                    '$unchanged===' => 'array<int<0, max>, null|object>',
                ],
            ],
            'fcloseTakesTheStandardStreamConstants' => [
                'code' => '<?php
                    fclose(STDIN);
                    fclose(STDOUT);
                    fclose(STDERR);',
            ],
            'domXPathEvaluateReturnsATypedResult' => [
                'code' => '<?php
                    $xpath = new DOMXPath(new DOMDocument());
                    $value = $xpath->evaluate("count(//a)");

                    if (is_numeric($value)) {
                        echo "number";
                    }

                    if (is_string($value)) {
                        echo "string";
                    }

                    if ($value instanceof DOMNodeList) {
                        echo "nodes";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'filterVarIntWithFloatLiteralsOutsideTheRangeOfAnInt' => [
                'code' => '<?php
                    $big = filter_var(1e30, FILTER_VALIDATE_INT);
                    $notANumber = filter_var(NAN, FILTER_VALIDATE_INT);
                    $whole = filter_var(5.0, FILTER_VALIDATE_INT);
                    $bigMin = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1e30]]);
                    $notANumberMax = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["max_range" => NAN]]);
                    $knownMinAndNotANumberMax = filter_var(
                        5,
                        FILTER_VALIDATE_INT,
                        ["options" => ["min_range" => 0, "max_range" => NAN]],
                    );
                    $bigMinAndKnownMax = filter_var(
                        5,
                        FILTER_VALIDATE_INT,
                        ["options" => ["min_range" => 1e30, "max_range" => 10]],
                    );
                    $knownRange = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 10]]);',
                'assertions' => [
                    '$big===' => 'false',
                    '$notANumber===' => 'false',
                    '$whole===' => '5',
                    '$bigMin===' => 'false|int',
                    '$notANumberMax===' => 'false|int',
                    '$knownMinAndNotANumberMax===' => 'false|int',
                    '$bigMinAndKnownMax===' => 'false|int',
                    '$knownRange===' => '5',
                ],
            ],

            'multibyteLiteralArgument' => [
                'code' => '<?php
                    function startsWithGreeting(string $s): bool {
                        return str_starts_with($s, "здравствуй");
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classHierarchyFunctionsOfAnObjectNeverReturnFalse' => [
                'code' => '<?php
                    /** @var mixed $mixed */
                    $mixed = null;
                    /** @var object $object */
                    $object = new stdClass();
                    /** @var string $string */
                    $string = "";
                    /** @var object|string $either */
                    $either = "";
                    /** @var bool $autoload */
                    $autoload = true;

                    if (!is_object($mixed)) {
                        throw new Exception();
                    }

                    $parents = class_parents($mixed);
                    $interfaces = class_implements($object, $autoload);
                    $traits = class_uses(object_or_class: $object, autoload: false);
                    $parents_of_name = class_parents($string);
                    $interfaces_of_name = class_implements($string, false);
                    $traits_of_name = class_uses($string);
                    $parents_of_either = class_parents($either);',
                'assertions' => [
                    '$parents===' => 'array<class-string, class-string>',
                    '$interfaces===' => 'array<interface-string, interface-string>',
                    '$traits===' => 'array<trait-string, trait-string>',
                    '$parents_of_name===' => 'array<class-string, class-string>|false',
                    '$interfaces_of_name===' => 'array<interface-string, interface-string>|false',
                    '$traits_of_name===' => 'array<trait-string, trait-string>|false',
                    '$parents_of_either===' => 'array<class-string, class-string>|false',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classHierarchyFunctionsOfAClassNameAreNotPossiblyFalseArguments' => [
                'code' => '<?php
                    interface I {}

                    /** @param class-string $class */
                    function f(string $class): bool {
                        return in_array(I::class, class_implements($class), true)
                            || in_array(I::class, class_parents($class), true)
                            || in_array(I::class, class_uses($class), true);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'substrOfANonEmptyStringFromItsStart' => [
                'code' => '<?php
                    /** @var non-empty-string $in */
                    $in = "a";
                    /** @var string $any */
                    $any = "";
                    /** @var int $int */
                    $int = 1;
                    /** @var int<1, max> $positive */
                    $positive = 1;
                    /** @var int<min, -1> $negative */
                    $negative = -1;
                    /** @var int<0, max> $not_negative */
                    $not_negative = 0;
                    /** @var int<min, 0> $not_positive */
                    $not_positive = 0;

                    $prefix = substr($in, 0, 4);
                    $tail = substr($in, -3);
                    $whole = substr($in, 0);
                    $explicit_null = substr($in, 0, null);
                    $dynamic_length = substr($in, 0, $positive);
                    $dynamic_offset = substr($in, $not_positive, 2);
                    $literal = substr("abc", 0, 2);

                    $zero_length = substr($in, 0, 0);
                    $unknown_length = substr($in, 0, $int);
                    $not_negative_length = substr($in, 0, $not_negative);
                    $negative_length = substr($in, 0, $negative);
                    $later_offset = substr($in, 1, 4);
                    $unknown_offset = substr($in, $int, 4);
                    $positive_offset = substr($in, $positive, 4);
                    $maybe_empty_input = substr($any, 0, 4);
                    $empty_literal = substr("", 0, 4);',
                'assertions' => [
                    '$prefix===' => 'non-empty-string',
                    '$tail===' => 'non-empty-string',
                    '$whole===' => 'non-empty-string',
                    '$explicit_null===' => 'non-empty-string',
                    '$dynamic_length===' => 'non-empty-string',
                    '$dynamic_offset===' => 'non-empty-string',
                    '$literal===' => 'non-empty-string',
                    '$zero_length===' => 'string',
                    '$unknown_length===' => 'string',
                    '$not_negative_length===' => 'string',
                    '$negative_length===' => 'string',
                    '$later_offset===' => 'string',
                    '$unknown_offset===' => 'string',
                    '$positive_offset===' => 'string',
                    '$maybe_empty_input===' => 'string',
                    '$empty_literal===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'substrBeforePhp8CanReturnFalse' => [
                'code' => '<?php
                    /** @var non-empty-string $in */
                    $in = "a";
                    $prefix = substr($in, 0, 4);',
                'assertions' => [
                    '$prefix===' => 'false|string',
                ],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'errorGetLastHasBacktraceSincePhp85' => [
                'code' => '<?php
                    $error = error_get_last();',
                'assertions' => [
                    '$error===' => 'array{file: string, line: int, message: string, trace?: list<array{args?: list<mixed>, class?: class-string, file?: string, function: string, line?: int, object?: object, type?: string}>, type: int}|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'errorGetLastHasNoBacktraceBeforePhp85' => [
                'code' => '<?php
                    $error = error_get_last();',
                'assertions' => [
                    '$error===' => 'array{file: string, line: int, message: string, type: int}|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'peclFunctionsOfExtensionsThePhp86ImageLacks' => [
                'code' => '<?php
                    $session = ssh2_connect("example.com", 22);
                    $version = swoole_version();
                    $string = _string("abc");
                    $serialized = igbinary_serialize([1]);',
                'assertions' => [
                    '$version===' => 'string',
                    '$string===' => 'Swoole\StringObject',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.6',
            ],
            'clampOfIntegers' => [
                'code' => '<?php
                    /** @var int $i */;
                    $result = clamp($i, 0, 100);',
                'assertions' => [
                    '$result===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfFloats' => [
                'code' => '<?php
                    /** @var float $f */;
                    $result = clamp($f, 0.0, 1.0);',
                'assertions' => [
                    '$result===' => 'float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfMixedIntAndFloat' => [
                'code' => '<?php
                    /** @var int $i */;
                    /** @var float $f */;
                    $result = clamp($i, 0.5, $f);',
                'assertions' => [
                    '$result===' => 'float|int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfObjects' => [
                'code' => '<?php
                    $d = new DateTimeImmutable();
                    $min = new DateTimeImmutable("yesterday");
                    $max = new DateTimeImmutable("tomorrow");
                    $result = clamp($d, $min, $max);',
                'assertions' => [
                    '$result===' => 'DateTimeImmutable',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'clampOfStrings' => [
                'code' => '<?php
                    /** @var string $s */;
                    $result = clamp($s, "a", "z");',
                'assertions' => [
                    '$result===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'callTemplatedFunctionWithUnionArgument' => [
                'code' => '<?php
                    /** @template T */
                    interface Message {}

                    /** @implements Message<int> */
                    final class FirstMessage implements Message {}

                    /** @implements Message<int> */
                    final class SecondMessage implements Message {}

                    /**
                     * @template T
                     * @param Message<T> $msg
                     */
                    function test(Message $msg): void {}

                    /** @var FirstMessage|SecondMessage $message */;
                    test($message);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'callFunctionWithTemplateClassStringWillNotFail' => [
                'code' => '<?php
                    /** @param class-string<SplFixedArray<string>> $classString */
                    function acceptTemplatedClassString(string $classString): void
                    {
                    }

                    /** @param class-string<SplFixedArray<string>> $classString */
                    function app(string $classString): void
                    {
                        acceptTemplatedClassString($classString);
                    }',
            ],
            'inferGenericListFromTuple' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @param list<A> $list
                     * @return list<A>
                     */
                    function testList(array $list): array { return $list; }
                    /**
                     * @template A
                     * @param non-empty-list<A> $list
                     * @return non-empty-list<A>
                     */
                    function testNonEmptyList(array $list): array { return $list; }
                    /**
                     * @template A of list<mixed>
                     * @param A $list
                     * @return A
                     */
                    function testGenericList(array $list): array { return $list; }
                    $list = testList([1, 2, 3]);
                    $nonEmptyList = testNonEmptyList([1, 2, 3]);
                    $genericList = testGenericList([1, 2, 3]);',
                'assertions' => [
                    '$list===' => 'list<1|2|3>',
                    '$nonEmptyList===' => 'non-empty-list<1|2|3>',
                    '$genericList===' => 'list{1, 2, 3}',
                ],
            ],
            'inferIterableFromTraversable' => [
                'code' => '<?php
                    /**
                     * @return SplFixedArray<string>
                     */
                    function getStrings(): SplFixedArray
                    {
                        return SplFixedArray::fromArray(["fst", "snd", "thr"]);
                    }
                    /**
                     * @return SplFixedArray<int>
                     */
                    function getIntegers(): SplFixedArray
                    {
                        return SplFixedArray::fromArray([1, 2, 3]);
                    }
                    /**
                     * @template K
                     * @template A
                     * @template B
                     * @param iterable<K, A> $lhs
                     * @param iterable<K, B> $rhs
                     * @return iterable<K, A|B>
                     */
                    function mergeIterable(iterable $lhs, iterable $rhs): iterable
                    {
                        foreach ($lhs as $k => $v) { yield $k => $v; }
                        foreach ($rhs as $k => $v) { yield $k => $v; }
                    }
                    $iterable = mergeIterable(getStrings(), getIntegers());',
                'assertions' => [
                    '$iterable===' => 'iterable<int, int|string>',
                ],
            ],
            'inferTypeFromAnonymousObjectWithTemplatedProperty' => [
                'code' => '<?php
                    /** @template T */
                    final class Value
                    {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }
                    /**
                     * @template T
                     * @param object{value: T} $object
                     * @return T
                     */
                    function getValue(object $object): mixed
                    {
                        return $object->value;
                    }
                    /**
                     * @template T
                     * @param object{value: object{value: T}} $object
                     * @return T
                     */
                    function getNestedValue(object $object): mixed
                    {
                        return $object->value->value;
                    }
                    $object = new Value(new Value(42));
                    $value = getValue($object);
                    $nestedValue = getNestedValue($object);',
                'assertions' => [
                    '$value===' => 'Value<42>',
                    '$nestedValue===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'paramDefaultNewObjectImplementingInterface' => [
                'code' => '<?php
                    function f(Traversable $p = new ArrayObject()): void {}
                    function g(Countable&Traversable $p = new ArrayObject()): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'paramDefaultNonFinalClassMayImplementInterface' => [
                'code' => '<?php
                    interface I {}
                    class Foo {}
                    class Impl extends Foo implements I {}
                    /** @var Foo */
                    const C = new Impl();
                    function f(I $p = C): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferTypeFromAnonymousObjectWithTemplatedPropertyFromTemplatedAncestor' => [
                'code' => '<?php
                    /** @template T */
                    abstract class AbstractValue
                    {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }
                    /**
                     * @template TValue
                     * @extends AbstractValue<TValue>
                     */
                    final class ConcreteValue extends AbstractValue
                    {
                        /**
                         * @param TValue $value
                         */
                        public function __construct(mixed $value)
                        {
                            parent::__construct($value);
                        }
                    }
                    /**
                     * @template T
                     * @param object{value: T} $object
                     * @return T
                     */
                    function getValue(object $object): mixed
                    {
                        return $object->value;
                    }
                    /**
                     * @template T
                     * @param object{value: object{value: T}} $object
                     * @return T
                     */
                    function getNestedValue(object $object): mixed
                    {
                        return $object->value->value;
                    }
                    $object = new ConcreteValue(new ConcreteValue(42));
                    $value = getValue($object);
                    $nestedValue = getNestedValue($object);',
                'assertions' => [
                    '$value===' => 'ConcreteValue<42>',
                    '$nestedValue===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferTypeFromAnonymousObjectWithTemplatedPropertyFromConcreteAncestor' => [
                'code' => '<?php
                    /** @template T */
                    abstract class AbstractValue
                    {
                        /** @param T $value */
                        public function __construct(public readonly mixed $value) {}
                    }
                    /** @extends AbstractValue<int> */
                    final class IntValue extends AbstractValue {}
                    final class Nested
                    {
                        public function __construct(public readonly IntValue $value) {}
                    }
                    /**
                     * @template T
                     * @param object{value: T} $object
                     * @return T
                     */
                    function getValue(object $object): mixed
                    {
                        return $object->value;
                    }
                    /**
                     * @template T
                     * @param object{value: object{value: T}} $object
                     * @return T
                     */
                    function getNestedValue(object $object): mixed
                    {
                        return $object->value->value;
                    }
                    $object = new Nested(new IntValue(42));
                    $value = getValue($object);
                    $nestedValue = getNestedValue($object);',
                'assertions' => [
                    '$value===' => 'IntValue',
                    '$nestedValue===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'countShapedArrays' => [
                'code' => '<?php
                    /** @var array{a?: int} */
                    $a = [];
                    $aCount = count($a);

                    /** @var array{a: int} */
                    $b = [];
                    $bCount = count($b);

                    /** @var array{a: int, b?: int} */
                    $c = [];
                    $cCount = count($c);

                    /** @var array{a: int}&array */
                    $d = [];
                    $dCount = count($d);

                    /** @var list{0?: int} */
                    $e = [];
                    $eCount = count($e);

                    /** @var list{int} */
                    $f = [];
                    $fCount = count($f);

                    /** @var list{0: int, 1?: int} */
                    $g = [];
                    $gCount = count($g);

                    /** @var list{0: int, 1?: int}&array */
                    $h = [];
                    $hCount = count($h);',
                'assertions' => [
                    '$aCount===' => 'int<0, 1>',
                    '$bCount===' => '1',
                    '$cCount===' => 'int<1, 2>',
                    '$dCount===' => 'int<1, max>',
                    '$eCount===' => 'int<0, 1>',
                    '$fCount===' => '1',
                    '$gCount===' => 'int<1, 2>',
                    '$hCount===' => 'int<1, max>',
                ],
            ],
            'preg_grep' => [
                'code' => '<?php
                  /**
                   * @param array<int,string> $strings
                   * @return array<int,string>
                   */
                  function filter(array $strings): array {
                     return preg_grep("/search/", $strings, PREG_GREP_INVERT);
                  }
                ',
            ],
            'typedArrayWithDefault' => [
                'code' => '<?php
                    class A {}

                    /** @param array<A> $a */
                    function fooFoo(array $a = []): void {

                    }',
            ],
            'abs' => [
                'code' => '<?php
                    $a = abs(-5);
                    $b = abs(-7.5);
                    $c = $_GET["c"];
                    $c = is_numeric($c) ? abs($c) : null;',
                'assertions' => [
                    '$a' => 'int<0, max>',
                    '$b' => 'float',
                    '$c' => 'float|int<0, max>|null',
                ],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'validDocblockParamDefault' => [
                'code' => '<?php
                    /**
                     * @param  int|false $p
                     * @return void
                     */
                    function f($p = false) {}',
            ],
            'byRefNewString' => [
                'code' => '<?php
                    function fooFoo(?string &$v): void {}
                    fooFoo($a);',
            ],
            'byRefVariableFunctionExistingArray' => [
                'code' => '<?php
                    $arr = [];
                    function fooFoo(array &$v): void {}
                    $function = "fooFoo";
                    $function($arr);
                    if ($arr) {}',
            ],
            'byRefProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();

                    function fooFoo(string &$v): void {}

                    fooFoo($a->foo);',
            ],
            'namespaced' => [
                'code' => '<?php
                    namespace A;

                    /** @return void */
                    function f(int $p) {}
                    f(5);',
            ],
            'namespacedRootFunctionCall' => [
                'code' => '<?php
                    namespace {
                        /** @return void */
                        function foo() { }
                    }
                    namespace A\B\C {
                        foo();
                    }',
            ],
            'namespacedAliasedFunctionCall' => [
                'code' => '<?php
                    namespace Aye {
                        /** @return void */
                        function foo() { }
                    }
                    namespace Bee {
                        use Aye as A;

                        A\foo();
                    }',
            ],
            'noRedundantConditionAfterArrayObjectCountCheck' => [
                'code' => '<?php
                    /** @var ArrayObject<int, int> */
                    $a = [];
                    $b = 5;
                    if (count($a)) {}',
            ],
            'noRedundantConditionAfterMixedOrEmptyArrayCountCheck' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        $a = $GLOBALS["s"] ?: [];
                        if (count($a)) {}
                        if (!count($a)) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'noRedundantErrorForCallableStrToLower' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var callable-string */
                    $function = "strlen";
                    strtolower($function);
                PHP,
            ],
            'objectLikeArrayAssignmentInConditional' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a["a"] = 5;
                    }

                    if (count($a)) {}
                    if (!count($a)) {}',
            ],
            'noRedundantConditionAfterCheckingExplodeLength' => [
                'code' => '<?php
                    /** @var string */
                    $s = "hello";
                    $segments = explode(".", $s);
                    if (count($segments) === 1) {}',
            ],
            'arrayPopNonEmptyAfterThreeAssertions' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                        /** @var array<int, string> */
                        public $arr = [];
                    }

                    /** @var array<A> */
                    $replacement_stmts = [];

                    if (!$replacement_stmts
                        || !$replacement_stmts[0] instanceof B
                        || count($replacement_stmts[0]->arr) > 1
                    ) {
                        return null;
                    }

                    $b = $replacement_stmts[0]->arr;',
                'assertions' => [
                    '$b' => 'array<int, string>',
                ],
            ],
            'countMoreThan0CanBeInverted' => [
                'code' => '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = "hello";
                    }

                    if (count($a) > 0) {
                        exit;
                    }',
                    'assertions' => [
                        '$a' => 'array<never, never>',
                    ],
            ],
            'countCheckOnNonEmptyArray' => [
                'code' => '<?php
                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr): void {
                        if (count($arr) > 5) {}
                    }',
            ],
            'byRefAfterCallable' => [
                'code' => '<?php
                    /**
                     * @param callable $callback
                     * @return void
                     */
                    function route($callback) {
                      if (!is_callable($callback)) {  }
                      $a = preg_match("//", "", $b);
                      if ($b[0]) {}
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'ignoreNullablePregReplace' => [
                'code' => '<?php
                    function foo(string $s): string {
                        $s = preg_replace("/hello/", "", $s);
                        if ($s === null) {
                            return "hello";
                        }
                        return $s;
                    }
                    function bar(string $s): string {
                        $s = preg_replace("/hello/", "", $s);
                        return $s;
                    }
                    function bat(string $s): ?string {
                        $s = preg_replace("/hello/", "", $s);
                        return $s;
                    }',
            ],
            'pregReplaceArrayValueType' => [
                'code' => '<?php
                    /**
                     * @param string[] $s
                     * @return string[]
                     */
                    function foo($s): array {
                        return preg_replace("/hello/", "", $s);
                    }',
            ],
            'extractVarCheck' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return array{a: 15, ...}
                     */
                    function getUnsealedArray() {}
                    function takesString(string $str): void {}

                    $foo = "foo";
                    $a = getUnsealedArray();
                    extract($a);
                    takesString($foo);',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedArgument',
                ],
            ],
            'extractVarCheckValid' => [
                'code' => '<?php
                    function takesInt(int $i): void {}

                    $foo = "foo";
                    $a = [$foo => 15];
                    extract($a);
                    takesInt($foo);',
            ],
            'extractSkipExtr' => [
                'code' => '<?php
                    $a = 1;

                    extract(["a" => "x", "b" => "y"], EXTR_SKIP);',
                'assertions' => [
                    '$a===' => '1',
                    '$b===' => '\'y\'',
                ],
            ],
            'compact' => [
                'code' => '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function test(): array {
                        return compact(["val"]);
                    }',
            ],
            'objectLikeKeyChecksAgainstGeneric' => [
                'code' => '<?php
                    /**
                     * @param array<string, string> $b
                     */
                    function a($b): string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
            'objectLikeKeyChecksAgainstTKeyedArray' => [
                'code' => '<?php
                    /**
                     * @param array{a: string} $b
                     */
                    function a($b): string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
            'getenvCalledWithOtherCasing' => [
                'code' => '<?php
                    $a = getEnv("some_key");
                    $b = GETENV();
                    $c = getenv("some_key", true);',
                'assertions' => [
                    '$a===' => 'false|string',
                    '$b===' => 'array<string, string>',
                    '$c===' => 'false|string',
                ],
            ],
            'functionExistsGuardsTheRightOperandInsideAnotherNarrowingCondition' => [
                'code' => '<?php
                    function a(int $arg): void {
                        if ($arg) {
                            if (function_exists("do_something_a") && rand(0, 1)) {
                                do_something_a("hello");
                            }
                        }
                    }

                    function b(int $arg): void {
                        if (!empty($arg)) {
                            if (function_exists("do_something_b") && do_something_b("hello")) {
                                echo 1;
                            }
                        }
                    }

                    function c(int $arg): void {
                        if ($arg) {
                            if (defined("SOME_MISSING_CONSTANT") && SOME_MISSING_CONSTANT) {
                                echo 1;
                            }
                        }
                    }',
            ],
            'functionExistsGuardsOnlyTheFunctionsItNames' => [
                'code' => '<?php
                    function callable_string(int $arg): void {
                        if (function_exists("guarded_callback")) {
                            array_map("guarded_callback", [1]);
                        }
                    }

                    function namespaced_name(int $arg): void {
                        if ($arg) {
                            if (function_exists("\\guarded_root") && \guarded_root()) {
                                echo 1;
                            }
                        }
                    }

                    function guarded_class(int $arg): void {
                        if ($arg) {
                            if (class_exists("GuardedClass") && GuardedClass::run()) {
                                echo 1;
                            }
                        }
                    }

                    function guarded_class_body(): void {
                        if (class_exists("GuardedBodyClass") && rand(0, 1)) {
                            GuardedBodyClass::run();
                        }
                    }',
            ],
            'getenv' => [
                'code' => '<?php
                    $a = getenv();
                    $b = getenv("some_key");',
                'assertions' => [
                    '$a' => 'array<string, string>',
                    '$b' => 'false|string',
                ],
            ],
            'ignoreFalsableFileGetContents' => [
                'code' => '<?php
                    function foo(string $s): string {
                        return file_get_contents($s);
                    }
                    function bar(string $s): string {
                        $a = file_get_contents($s);
                        if ($a === false) {
                            return "hello";
                        }
                        return $a;
                    }
                    /**
                     * @return false|string
                     */
                    function bat(string $s) {
                        return file_get_contents($s);
                    }',
            ],
            'validCallables' => [
                'code' => '<?php
                    class A {
                        public static function b() : void {}
                    }

                    function c() : void {}

                    ["a", "b"]();
                    "A::b"();
                    "c"();',
            ],
            'noInvalidOperandForCoreFunctions' => [
                'code' => '<?php
                    function foo(string $a, string $b) : int {
                        $aTime = strtotime($a);
                        $bTime = strtotime($b);

                        return $aTime - $bTime;
                    }',
            ],
            'functionCallInGlobalScope' => [
                'code' => '<?php
                    $a = function() use ($argv) : void {};',
            ],
            'varExport' => [
                'code' => '<?php
                    $a = var_export(["a"], true);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'varExportConstFetch' => [
                'code' => '<?php
                    class Foo {
                        const BOOL_VAR_EXPORT_RETURN = true;

                        /**
                         * @param mixed $mixed
                         */
                        public static function Baz($mixed) : string {
                            return var_export($mixed, self::BOOL_VAR_EXPORT_RETURN);
                        }
                    }',
            ],
            'explode' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithPositiveLimit' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, 5);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithNegativeLimit' => [
                'code' => '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, -5);',
                'assertions' => [
                    '$elements' => 'array<never, never>',
                ],
            ],
            'explodeWithDynamicLimit' => [
                'code' => '<?php
                    /**
                     * @var string $string
                     * @var int $limit
                     */
                    $elements = explode(" ", $string, $limit);',
                'assertions' => [
                    '$elements' => 'list{0?: string, 1?: string, 2?: string, ...<string>}',
                ],
            ],
            'explodeWithDynamicDelimiter' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndSmallPositiveLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, 2);',
                'assertions' => [
                    '$elements' => 'list{0: string, 1?: string}',
                ],
            ],
            'explodeWithDynamicDelimiterAndPositiveLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, 5);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndNegativeLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, -5);',
                'assertions' => [
                    '$elements' => 'array<never, never>',
                ],
            ],
            'explodeWithDynamicDelimiterAndLimit' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     * @var int $limit
                     */
                    $elements = explode($delim, $string, $limit);',
                'assertions' => [
                    '$elements' => 'list{0?: string, 1?: string, 2?: string, ...<string>}',
                ],
            ],
            'explodeWithDynamicNonEmptyDelimiter' => [
                'code' => '<?php
                    /**
                     * @var non-empty-string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithLiteralNonEmptyDelimiter' => [
                'code' => '<?php
                    /**
                     * @var string $string
                     */
                    $elements = explode(" ", $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithPossiblyFalse' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $d
                     * @return non-empty-list<string>
                     */
                    function exploder(string $d, string $s) : array {
                        return explode($d, $s);
                    }',
            ],
            'assertAcceptsObjectAndNarrowsIt' => [
                'code' => '<?php
                    function f(?DateTime $d): DateTime {
                        assert($d);
                        return $d;
                    }
                    /** @var DateTime|null $foo */
                    $foo = null;
                    assert($foo);',
                'assertions' => [
                    '$foo===' => 'DateTime',
                ],
            ],
            'assertAcceptsArrayAndFloat' => [
                'code' => '<?php
                    function f(array $a, float $f): void {
                        assert($a);
                        assert($f);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'allowPossiblyUndefinedClassInClassExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (class_exists(Foo::class)) {}
                    PHP,
            ],
            'allowPossiblyUndefinedClassInInterfaceExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (interface_exists(Foo::class)) {}
                    PHP,
            ],
            'allowPossiblyUndefinedClassInTraitExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (trait_exists(Foo::class)) {}
                    PHP,
            ],
            'allowPossiblyUndefinedClassInEnumExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (enum_exists(Foo::class)) {}
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'allowConstructorAfterClassExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (class_exists($s)) {
                            new $s();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'next' => [
                'code' => '<?php
                    $arr = ["one", "two", "three"];
                    $n = next($arr);',
                'assertions' => [
                    '$n' => 'false|string',
                ],
            ],
            'iteratorToArray' => [
                'code' => '<?php
                    /**
                     * @return Generator<stdClass>
                     */
                    function generator(): Generator {
                        yield new stdClass;
                    }

                    $a = iterator_to_array(generator());',
                'assertions' => [
                    '$a' => 'array<array-key, stdClass>',
                ],
            ],
            'iteratorToArrayWithGetIterator' => [
                'code' => '<?php
                    /**
                     * @implements IteratorAggregate<int, string>
                     */
                    class C implements IteratorAggregate {
                        /**
                         * @return Traversable<int,string>
                         */
                        public function getIterator() {
                            yield 1 => "1";
                        }
                    }
                    $a = iterator_to_array(new C);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'iteratorToArrayWithGetIteratorReturningList' => [
                'code' => '<?php
                    /**
                     * @implements IteratorAggregate<int, string>
                     */
                    class C implements IteratorAggregate {
                        /**
                         * @return Traversable<int, string>
                         */
                        public function getIterator() {
                            yield 1 => "1";
                        }
                    }
                    $a = iterator_to_array(new C, false);',
                'assertions' => [
                    '$a' => 'list<string>',
                ],
            ],
            'strtrWithPossiblyFalseFirstArg' => [
                'code' => '<?php
                    /**
                     * @param false|string $str
                     * @param array<string, string> $replace_pairs
                     * @return string
                     */
                    function strtr_wrapper($str, array $replace_pairs) {
                        /** @psalm-suppress PossiblyFalseArgument */
                        return strtr($str, $replace_pairs);
                    }',
            ],
            'versionCompare' => [
                'code' => '<?php
                    /** @return "="|"==" */
                    function getString() : string {
                        return rand(0, 1) ? "==" : "=";
                    }

                    $a = version_compare("5.0.0", "7.0.0");
                    $b = version_compare("5.0.0", "7.0.0", "==");
                    $c = version_compare("5.0.0", "7.0.0", getString());
                ',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'bool',
                    '$c' => 'bool',
                ],
            ],
            'getTimeOfDay' => [
                'code' => '<?php
                    $a = gettimeofday(true) - gettimeofday(true);
                    $b = gettimeofday();
                    $c = gettimeofday(false);',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'array<string, int>',
                    '$c' => 'array<string, int>',
                ],
            ],
            'parseUrlArray' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        $parts = parse_url($s);
                        return $parts["host"] ?? "";
                    }

                    function hereisanotherone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function hereisthelastone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function portisint(string $s) : int {
                        $parsed = parse_url($s);

                        if (isset($parsed["port"])) {
                            return $parsed["port"];
                        }

                        return 80;
                    }

                    function portismaybeint(string $s) : ? int {
                        $parsed = parse_url($s);

                        return $parsed["port"] ?? null;
                    }

                    $porta = parse_url("", PHP_URL_PORT);
                    $porte = parse_url("localhost:443", PHP_URL_PORT);',
                'assertions' => [
                    '$porta' => 'false|int|null',
                    '$porte' => 'false|int|null',
                ],
                'ignored_issues' => ['MixedReturnStatement'],
            ],
            'parseUrlComponent' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        return parse_url($s, PHP_URL_HOST) ?? "";
                    }

                    function bar(string $s) : string {
                        return parse_url($s, PHP_URL_HOST);
                    }

                    function bag(string $s) : string {
                        $host = parse_url($s, PHP_URL_HOST);

                        if (is_string($host)) {
                            return $host;
                        }

                        return "";
                    }',
            ],
            'parseUrlTypes' => [
                'code' => '<?php
                    $url = "foo";
                    $components = parse_url($url);
                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    $host = parse_url($url, PHP_URL_HOST);
                    $port = parse_url($url, PHP_URL_PORT);
                    $user = parse_url($url, PHP_URL_USER);
                    $pass = parse_url($url, PHP_URL_PASS);
                    $path = parse_url($url, PHP_URL_PATH);
                    $query = parse_url($url, PHP_URL_QUERY);
                    $fragment = parse_url($url, PHP_URL_FRAGMENT);',
                'assertions' => [
                    '$components' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$scheme' => 'false|null|string',
                    '$host' => 'false|null|string',
                    '$port' => 'false|int|null',
                    '$user' => 'false|null|string',
                    '$pass' => 'false|null|string',
                    '$path' => 'false|null|string',
                    '$query' => 'false|null|string',
                    '$fragment' => 'false|null|string',
                ],
            ],
            'parseUrlDefaultComponent' => [
                'code' => '<?php
                    $component = -1;
                    $url = "foo";
                    $a = parse_url($url, -1);
                    $b = parse_url($url, -42);
                    $c = parse_url($url, $component);',
                'assertions' => [
                    '$a' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$b' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$c' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                ],
            ],
            'triggerUserError' => [
                'code' => '<?php
                    function mightLeave() : string {
                        if (rand(0, 1)) {
                            trigger_error("bad", E_USER_ERROR);
                        } else {
                            return "here";
                        }
                    }',
            ],
            'getParentClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    $b = get_parent_class(new A());
                    if ($b === false) {}
                    $c = new $b();',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'suppressError' => [
                'code' => '<?php
                    $a = @file_get_contents("foo");',
                'assertions' => [
                    '$a' => 'false|string',
                ],
            ],
            'echo' => [
                'code' => '<?php
                echo false;',
            ],
            'printrOutput' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        echo $s;
                    }

                    foo(print_r(1, true));',
            ],
            'microtime' => [
                'code' => '<?php
                    $a = microtime(true);
                    $b = microtime();
                    /** @psalm-suppress InvalidScalarArgument */
                    $c = microtime(1);
                    $d = microtime(false);',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'string',
                    '$c' => 'float|string',
                    '$d' => 'string',
                ],
            ],
            'filterInput' => [
                'code' => '<?php
                    $a = filter_input(INPUT_GET, "foo", options: FILTER_FORCE_ARRAY);
                    assert(is_array($a));

                    function filterInt(string $s) : int {
                        $filtered = filter_var($s, FILTER_VALIDATE_INT);
                        if ($filtered === false) {
                            return 0;
                        }
                        return $filtered;
                    }
                    function filterNullableInt(string $s) : ?int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => null]]);
                    }
                    function filterIntWithDefault(string $s) : int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => 5]]);
                    }
                    function filterBool(string $s) : bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN);
                    }
                    function filterNullableBool(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                    function filterNullableBoolWithFlagsArray(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, ["flags" => FILTER_NULL_ON_FAILURE]);
                    }
                    function filterFloat(string $s) : float {
                        $filtered = filter_var($s, FILTER_VALIDATE_FLOAT);
                        if ($filtered === false) {
                            return 0.0;
                        }
                        return $filtered;
                    }
                    function filterFloatWithDefault(string $s) : float {
                        return filter_var($s, FILTER_VALIDATE_FLOAT, ["options" => ["default" => 5.0]]);
                    }',
            ],
            'filterVar' => [
                'code' => '<?php
                    function namedArgs(): string {
                        $a = filter_var("a", options: FILTER_FORCE_ARRAY);
                        return $a[0];
                    }

                    function filterInt(string $s) : int {
                        $filtered = filter_var($s, FILTER_VALIDATE_INT);
                        if ($filtered === false) {
                            return 0;
                        }
                        return $filtered;
                    }
                    function filterNullableInt(string $s) : ?int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => null]]);
                    }
                    function filterIntWithDefault(string $s) : int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => 5]]);
                    }
                    function filterBool(string $s) : bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN);
                    }
                    function filterNullableBool(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                    function filterNullableBoolWithFlagsArray(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, ["flags" => FILTER_NULL_ON_FAILURE]);
                    }
                    function filterFloat(string $s) : float {
                        $filtered = filter_var($s, FILTER_VALIDATE_FLOAT);
                        if ($filtered === false) {
                            return 0.0;
                        }
                        return $filtered;
                    }
                    function filterFloatWithDefault(string $s) : float {
                        return filter_var($s, FILTER_VALIDATE_FLOAT, ["options" => ["default" => 5.0]]);
                    }

                    /**
                     * @param mixed $c
                     * @return int<1, 100>|stdClass|array<never, never>
                     */
                    function filterNumericIntWithDefault($c) {
                        if (is_numeric($c)) {
                            return filter_var($c, FILTER_VALIDATE_INT, [
                             "options" => [
                                "default"   => new stdClass(),
                                "min_range" => 1,
                                "max_range" => 100,
                            ],
                            ]);
                        }

                        return array();
                    }',
            ],
            'callVariableVar' => [
                'code' => '<?php
                    class Foo
                    {
                        public static function someInt(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @return int
                     */
                    function makeInt()
                    {
                        $fooClass = Foo::class;
                        return $fooClass::someInt();
                    }',
            ],
            'expectsIterable' => [
                'code' => '<?php
                    function foo(iterable $i) : void {}
                    function bar(array $a) : void {
                        foo($a);
                    }',
            ],
            'getTypeHasValues' => [
                'code' => '<?php
                    /**
                     * @param mixed $maybe
                     */
                    function matchesTypes($maybe) : void {
                        $t = gettype($maybe);
                        if ($t === "object") {}
                    }',
            ],
            'getTypeSwitchClosedResource' => [
                'code' => '<?php
                    $data = "foo";
                    switch (gettype($data)) {
                        case "resource (closed)":
                        case "unknown type":
                            return "foo";
                    }',
            ],
            'functionResolutionInNamespace' => [
                'code' => '<?php
                    namespace Foo;
                    function sort(int $_) : void {}
                    sort(5);',
            ],
            'rangeWithIntStep' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, 1) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithNoStep' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithNoStepAndString' => [
                'code' => '<?php

                    function foo(string $bar) : void {}

                    foreach (range("a", "z") as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithFloatStep' => [
                'code' => '<?php

                    function foo(float $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, .3) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithFloatStart' => [
                'code' => '<?php

                    function foo(float $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1.5, 10) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithIntOrFloatStep' => [
                'code' => '<?php
                    /** @var int|float */
                    $step = 1;
                    $a = range(1, 10, $step);

                    /** @var int */
                    $step = 1;
                    $b = range(1, 10, $step);

                    /** @var float */
                    $step = 1.;
                    $c = range(1, 10, $step);
                ',
                'assertions' => [
                    '$a' => 'non-empty-list<float|int>',
                    '$b' => 'non-empty-list<int>',
                    '$c' => 'non-empty-list<float>',
                ],
            ],
            'duplicateNamespacedFunction' => [
                'code' => '<?php
                    namespace Bar;

                    function sort() : void {}',
            ],
            'arrayMapAfterFunctionMissingFile' => [
                'code' => '<?php
                    require_once(FOO);
                    $urls = array_map("strval", [1, 2, 3]);',
                'assertions' => [],
                'ignored_issues' => ['UndefinedConstant', 'UnresolvableInclude'],
            ],
            'noNamespaceClash' => [
                'code' => '<?php
                    namespace FunctionNamespace {
                        function foo() : void {}
                    }

                    namespace ClassNamespace {
                        class Foo {}
                    }

                    namespace {
                        use ClassNamespace\Foo;
                        use function FunctionNamespace\foo;

                        new Foo();

                        foo();
                    }',
            ],
            'hashInit70' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'resource',
                ],
                'ignored_issues' => [],
                'php_version' => '7.1',
            ],
            'hashInit71' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'resource',
                ],
                'ignored_issues' => [],
                'php_version' => '7.1',
            ],
            'hashInit72' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'HashContext|false',
                ],
                'ignored_issues' => [],
                'php_version' => '7.2',
            ],
            'hashInit73' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'HashContext|false',
                ],
                'ignored_issues' => [],
                'php_version' => '7.3',
            ],
            'hashInit80' => [
                'code' => '<?php
                    $h = hash_init("sha256");',
                'assertions' => [
                    '$h' => 'HashContext',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullableByRef' => [
                'code' => '<?php
                    function foo(?string &$s) : void {}

                    function bar() : void {
                        foo($bar);
                    }',
            ],
            'getClassNewInstance' => [
                'code' => '<?php
                    interface I {}
                    class C implements I {}

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class(new C);',
            ],
            'getClassVariable' => [
                'code' => '<?php
                    interface I {}
                    class C implements I {}
                    $c_instance = new C;

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class($c_instance);',
            ],
            'getClassAnonymousNewInstance' => [
                'code' => '<?php
                    interface I {}

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class(new class implements I{});',
            ],
            'getClassAnonymousVariable' => [
                'code' => '<?php
                    interface I {}
                    $anon_instance = new class implements I {};

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class($anon_instance);',
            ],
            'mktime' => [
                'code' => '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    $a = mktime("foo");
                    /** @psalm-suppress MixedArgument */
                    $b = mktime($GLOBALS["foo"]);
                    $c = mktime(1, 2, 3);',
                'assertions' => [
                    '$a' => 'false|int',
                    '$b' => 'false|int',
                    '$c' => 'int',
                ],
            ],
            'hrtime' => [
                'code' => '<?php
                    $a = hrtime(true);
                    $b = hrtime();
                    /** @psalm-suppress InvalidScalarArgument */
                    $c = hrtime(1);
                    $d = hrtime(false);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'list{int, int}',
                    '$c' => 'int|list{int, int}',
                    '$d' => 'list{int, int}',
                ],
            ],
            'hrtimeCanBeFloat' => [
                'code' => '<?php
                    $a = hrtime(true);

                    if (is_int($a)) {}
                    if (is_float($a)) {}',
            ],
            'min' => [
                'code' => '<?php
                    $a = min(0, 1);
                    $b = min([0, 1]);
                    $c = min("a", "b");
                    $d = min(1, 2, 3, 4);
                    $e = min(1, 2, 3, 4, 5);
                    $f = min(...[1, 2, 3]);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'string',
                    '$d' => 'int',
                    '$e' => 'int',
                    '$f' => 'int',
                ],
            ],
            'minUnpackedArg' => [
                'code' => '<?php
                    $f = min(...[1, 2, 3]);',
                'assertions' => [
                    '$f' => 'int',
                ],
            ],
            'sscanf' => [
                'code' => '<?php
                    sscanf("10:05:03", "%d:%d:%d", $hours, $minutes, $seconds);',
                'assertions' => [
                    '$hours' => 'float|int|null|string',
                    '$minutes' => 'float|int|null|string',
                    '$seconds' => 'float|int|null|string',
                ],
            ],
            'noImplicitAssignmentToStringFromMixedWithDocblockTypes' => [
                'code' => '<?php
                    /** @param string $s */
                    function takesString($s) : void {}
                    function takesInt(int $i) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesString($s);
                        takesInt($s);
                    }',
            ],
            'ignoreNullableIssuesAfterMixedCoercion' => [
                'code' => '<?php
                    function takesNullableString(?string $s) : void {}
                    function takesString(string $s) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesNullableString($s);
                        takesString($s);
                    }',
            ],
            'countableSimpleXmlElement' => [
                'code' => '<?php
                    $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><a><b></b><b></b></a>");
                    echo count($xml);',
            ],
            'countableCallableArray' => [
                'code' => '<?php
                    /** @param callable|false $x */
                    function example($x) : void {
                        if (is_array($x)) {
                            echo "Count is: " . count($x);
                        }
                    }',
            ],
            'countNonEmptyArrayShouldBePositiveInt' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param non-empty-list $x
                     * @return positive-int
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countListShouldBeZeroOrPositive' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param list $x
                     * @return positive-int|0
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countArrayShouldBeZeroOrPositive' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array $x
                     * @return positive-int|0
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countEmptyArrayShouldBeZero' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array<never, never> $x
                     * @return 0
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countConstantSizeArrayShouldBeConstantInteger' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @param array{int, int, string} $x
                     * @return 3
                     */
                    function example($x) : int {
                        return count($x);
                    }',
            ],
            'countCallableArrayShouldBe2' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return 2
                     */
                    function example(callable $x) : int {
                        assert(is_array($x));
                        return count($x);
                    }',
            ],
            'countOnObjectShouldBePositive' => [
                'code' => '<?php
                    /** @return positive-int|0 */
                    function example(\Countable $x) : int {
                        return count($x);
                    }',
            ],
            'countOnPureObjectIsPure' => [
                'code' => '<?php
                    class PureCountable implements \Countable {
                        /** @psalm-pure */
                        public function count(): int { return 1; }
                    }
                    /** @psalm-pure */
                    function example(PureCountable $x) : int {
                        return count($x);
                    }',
            ],
            'refineWithTraitExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
            ],
            'refineWithClassExistsOrTraitExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s) || class_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function bar(string $s) : void {
                        if (class_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function baz(string $s) : void {
                        if (class_exists($s) || interface_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
            ],
            'minSingleArg' => [
                'code' => '<?php
                    /** @psalm-suppress TooFewArguments */
                    min(0);',
            ],
            'allowIsCountableToInformType' => [
                'code' => '<?php
                    function getObject() : iterable{
                       return [];
                    }

                    $iterableObject = getObject();

                    if (is_countable($iterableObject)) {
                       if (count($iterableObject) === 0) {}
                    }',
            ],
            'versionCompareAsCallable' => [
                'code' => '<?php
                    $a = ["1.0", "2.0"];
                    usort($a, "version_compare");',
            ],
            'coerceToObjectAfterBeingCalled' => [
                'code' => '<?php
                    class Foo {
                        public function bar() : void {}
                    }

                    function takesFoo(Foo $foo) : void {}

                    /** @param mixed $f */
                    function takesMixed($f) : void {
                        if (rand(0, 1)) {
                            $f = new Foo();
                        }
                        /** @psalm-suppress MixedArgument */
                        takesFoo($f);
                        $f->bar();
                    }',
            ],
            'functionExists' => [
                'code' => '<?php
                    if (!function_exists("in_array")) {
                        function in_array($a, $b) {
                            return true;
                        }
                    }',
            ],
            'callableArgumentWithFunctionExists' => [
                'code' => <<<'PHP'
                    <?php
                    if (function_exists('foo')) {
                        register_shutdown_function('foo');
                    }
                    PHP,
            ],
            'pregMatch' => [
                'code' => '<?php
                    function takesInt(int $i) : void {}

                    takesInt(preg_match("{foo}", "foo"));',
            ],
            'pregMatch2' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo");',
                'assertions' => [
                    '$r===' => '0|1|false',
                ],
            ],
            'pregMatchWithMatches' => [
                'code' => '<?php
                    /** @param string[] $matches */
                    function takesMatches(array $matches) : void {}

                    preg_match("{foo}", "foo", $matches);

                    takesMatches($matches);',
            ],
            'pregMatchWithMatches2' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, string>',
                ],
            ],
            'pregMatchWithOffset' => [
                'code' => '<?php
                    /** @param string[] $matches */
                    function takesMatches(array $matches) : void {}

                    preg_match("{foo}", "foo", $matches, 0, 10);

                    takesMatches($matches);',
            ],
            'pregMatchWithOffset2' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, 0, 10);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, string>',
                ],
            ],
            'pregMatchWithFlags' => [
                'code' => '<?php
                    function takesInt(int $i) : void {}

                    if (preg_match("{foo}", "this is foo", $matches, PREG_OFFSET_CAPTURE)) {
                        takesInt($matches[0][1]);
                    }',
            ],
            'pregMatchWithFlagOffsetCapture' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, PREG_OFFSET_CAPTURE);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, list{string, int<-1, max>}>',
                ],
            ],
            'pregMatchWithFlagUnmatchedAsNull' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, PREG_UNMATCHED_AS_NULL);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, null|string>',
                ],
            ],
            'pregMatchWithFlagOffsetCaptureAndUnmatchedAsNull' => [
                'code' => '<?php
                    $r = preg_match("{foo}", "foo", $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);',
                'assertions' => [
                    '$r===' => '0|1|false',
                    '$matches===' => 'array<array-key, list{null|string, int<-1, max>}>',
                ],
            ],
            'pregReplaceCallback' => [
                'code' => '<?php
                    function foo(string $s) : string {
                        return preg_replace_callback(
                            \'/<files (psalm-version="[^"]+") (?:php-version="(.+)">\n)/\',
                            /** @param string[] $matches */
                            function (array $matches) : string {
                                return $matches[1];
                            },
                            $s
                        );
                    }',
            ],
            'pregReplaceCallbackWithArray' => [
                'code' => '<?php
                    /**
                     * @param string[] $ids
                     */
                    function(array $ids): array {
                        return \preg_replace_callback(
                            "//",
                            fn (array $matches) => $matches[4],
                            $ids
                        );
                    };',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' => '7.4',
            ],
            'compactDefinedVariable' => [
                'code' => '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function foo(int $a, string $b, bool $c) : array {
                        return compact("a", "b", "c");
                    }',
            ],
            'setCookiePhp73' => [
                'code' => '<?php
                    setcookie(
                        "name",
                        "value",
                        [
                            "path"     => "/",
                            "expires"  => 0,
                            "httponly" => true,
                            "secure"   => true,
                            "samesite" => "Lax"
                        ]
                    );',
            ],
            'printrBadArg' => [
                'code' => '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    $a = print_r([], 1);
                    echo $a;',
            ],
            'dontCoerceCallMapArgs' => [
                'code' => '<?php
                    function getStr() : ?string {
                        return rand(0,1) ? "test" : null;
                    }

                    function test() : void {
                        $g = getStr();
                        /** @psalm-suppress PossiblyNullArgument */
                        $x = strtoupper($g);
                        $c = "prefix " . (strtoupper($g ?? "") === "x" ? "xa" : "ya");
                        echo "$x, $c\n";
                    }',
            ],
            'mysqliRealConnectFunctionAllowsNullParameters' => [
                'code' => '<?php
                    $mysqli = mysqli_init();
                    mysqli_real_connect($mysqli, null, \'test\', null);',
            ],
            'callUserFunc' => [
                'code' => '<?php
                    $func = function(int $arg1, int $arg2) : int {
                        return $arg1 * $arg2;
                    };

                    $a = call_user_func($func, 2, 4);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'callUserFuncArray' => [
                'code' => '<?php
                    $func = function(int $arg1, int $arg2) : int {
                        return $arg1 * $arg2;
                    };

                    $a = call_user_func_array($func, [2, 4]);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'dateTest' => [
                'code' => '<?php
                    $y = date("Y");
                    $ym = date("Ym");
                    $y_m = date("Y-m");
                    $m = date("m");
                    $F = date("F");
                    $y2 = date("Y", 10000);
                    $F2 = date("F", 10000);
                    /** @psalm-suppress MixedArgument */
                    $F3 = date("F", $GLOBALS["F3"]);
                    $gm_y = gmdate("Y");
                    $gm_ym = gmdate("Ym");
                    $gm_m = gmdate("m");
                    $gm_F = gmdate("F");
                    $gm_y2 = gmdate("Y", 10000);
                    $gm_F2 = gmdate("F", 10000);
                    /** @psalm-suppress MixedArgument */
                    $gm_F3 = gmdate("F", $GLOBALS["F3"]);',
                'assertions' => [
                    '$y===' => 'numeric-string',
                    '$ym===' => 'numeric-string',
                    '$y_m===' => 'string',
                    '$m===' => 'numeric-string',
                    '$F===' => 'string',
                    '$y2===' => 'numeric-string',
                    '$F2===' => 'string',
                    '$F3===' => 'string',
                    '$gm_y===' => 'numeric-string',
                    '$gm_ym===' => 'numeric-string',
                    '$gm_m===' => 'numeric-string',
                    '$gm_F===' => 'string',
                    '$gm_y2===' => 'numeric-string',
                    '$gm_F2===' => 'string',
                    '$gm_F3===' => 'string',
                ],
            ],
            'sscanfReturnTypeWithTwoParameters' => [
                'code' => '<?php
                    $data = sscanf("42 psalm road", "%s %s");',
                'assertions' => [
                    '$data' => 'list<float|int|null|string>|null',
                ],
            ],
            'sscanfReturnTypeWithMoreThanTwoParameters' => [
                'code' => '<?php
                    $n = sscanf("42 psalm road", "%s %s", $p1, $p2);',
                'assertions' => [
                    '$n' => 'int',
                    '$p1' => 'float|int|null|string',
                    '$p2' => 'float|int|null|string',
                ],
            ],
            'writeArgsAllowed' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $pattern
                     * @param 0|256|512|768 $flags
                     * @return false|int
                     */
                    function safeMatch(string $pattern, string $subject, ?array $matches = null, int $flags = 0) {
                        return \preg_match($pattern, $subject, $matches, $flags);
                    }

                    safeMatch("/a/", "b");',
            ],
            'fgetcsv' => [
                'code' => '<?php
                    $headers = fgetcsv(fopen("test.txt", "r"));
                    if (empty($headers)) {
                        throw new Exception("invalid headers");
                    }
                    print_r(array_map("strval", $headers));',
            ],
            'allowListEqualToRange' => [
                'code' => '<?php
                    /** @param array<int, int> $two */
                    function collectCommit(array $one, array $two) : void {
                        if ($one && array_values($one) === array_values($two)) {}
                    }',
            ],
            'pregMatchAll' => [
                'code' => '<?php
                    /**
                     * @return array<list<string>>
                     */
                    function extractUsernames(string $input): array {
                        preg_match_all(\'/([a-zA-Z])*/\', $input, $matches);

                        return $matches;
                    }',
            ],
            'pregMatchAllOffsetCapture' => [
                'code' => '<?php
                    function foo(string $input): array {
                        preg_match_all(\'/([a-zA-Z])*/\', $input, $matches, PREG_OFFSET_CAPTURE);

                        return $matches[0];
                    }',
            ],
            'pregMatchAllReturnsFalse' => [
                'code' => '<?php
                    /**
                     * @return int|false
                     */
                    function badpattern() {
                        return @preg_match_all("foo", "foo", $matches);
                    }',
            ],
            'strposAllowDictionary' => [
                'code' => '<?php
                    function sayHello(string $format): void {
                        if (strpos("abcdefghijklmno", $format) !== false) {}
                    }',
            ],
            'curlInitIsResourceAllowedIn7x' => [
                'code' => '<?php
                    $ch = curl_init();
                    if (!is_resource($ch)) {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'pregSplit' => [
                'code' => '<?php
                    /** @return non-empty-list */
                    function foo(string $s) {
                        return preg_split("/ /", $s);
                    }',
            ],
            'pregSplitWithFlags' => [
                'code' => '<?php
                    /** @return list<string> */
                    function foo(string $s) {
                        return preg_split("/ /", $s, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                    }',
            ],
            'mbConvertEncodingWithArray' => [
                'code' => '<?php
                    /**
                     * @param array<int, string> $str
                     * @return array<int, string>
                     */
                    function test2(array $str): array {
                        return mb_convert_encoding($str, "UTF-8", "UTF-8");
                    }',
            ],
            'getDebugType' => [
                'code' => '<?php
                    function foo(mixed $var) : void {
                        switch (get_debug_type($var)) {
                            case "string":
                                echo "a string";
                                break;

                            case Exception::class;
                                echo "an Exception with message " . $var->getMessage();
                                break;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getTypeDoubleThenInt' => [
                'code' => '<?php
                    function safe_float(mixed $val): bool {
                        switch (gettype($val)) {
                            case "double":
                            case "integer":
                                return true;
                            // ... more cases omitted
                            default:
                                return false;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'maxWithFloats' => [
                'code' => '<?php
                    function foo(float $_float): void
                    {}

                    foo(max(1.1, 1.2));',
            ],
            'maxWithObjects' => [
                'code' => '<?php
                    function foo(DateTimeImmutable $fooDate): string
                    {
                        return $fooDate->format("Y");
                    }

                    foo(max(new DateTimeImmutable(), new DateTimeImmutable()));',
            ],
            'maxWithMisc' => [
                'code' => '<?php
                    $a = max(new DateTimeImmutable(), 1.2);',
                'assertions' => [
                    '$a' => 'DateTimeImmutable|float',
                ],
            ],
            'maxUnpackArray' => [
                'code' => '<?php
                    $files = [
                        __FILE__,
                        __FILE__,
                        __FILE__,
                        __FILE__,
                    ];

                    $a = array_map("filemtime", $files);
                    $b = array_map(
                        function (string $file): int {
                            return filemtime($file);
                        },
                        $files,
                    );
                    $A = max(filemtime(__FILE__), ...$a);
                    $B = max(filemtime(__FILE__), ...$b);

                    echo date("c", $A), "\n", date("c", $B);
                ',
            ],
            'maxUnpackArrayWithNonInt' => [
                'code' => '<?php
                    $max = max(1, 2, ...[new DateTime(), 3, 4]);
                ',
                'assertions' => [
                    '$max===' => '1|2|3|4|DateTime',
                ],
            ],
            'strtolowerEmptiness' => [
                'code' => '<?php
                    /** @param non-empty-string $s */
                    function foo(string $s) : void {
                        $s = strtolower($s);

                        foo($s);
                    }',
            ],
            'preventObjectLeakingFromCallmapReference' => [
                'code' => '<?php
                    function one(): void
                    {
                        try {
                            exec("", $output);
                        } catch (Exception $e){
                        }
                    }

                    function two(): array
                    {
                        exec("", $lines);
                        return $lines;
                    }',
            ],
            'execOutputEmptyArrayIsListOfStrings' => [
                'code' => '<?php
                    $bar = array();
                    exec("pwd", $bar);

                    $baz = [];
                    exec("pwd", $baz, $code);

                    $foo = null;
                    exec("pwd", $foo);

                    /** @var array<string> $existing */
                    $existing = [];
                    exec("pwd", $existing);',
                'assertions' => [
                    '$bar===' => 'list<string>',
                    '$baz===' => 'list<string>',
                    '$foo===' => 'list<string>',
                    '$existing===' => 'array<array-key, mixed>',
                ],
            ],
            'array_is_list' => [
                'code' => '<?php
                    function getArray() : array {
                        return [];
                    }
                    $s = getArray();
                    assert(array_is_list($s));
                    ',
                'assertions' => [
                    '$s' => 'list<mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'trimOfALiteralStringIsALiteralString' => [
                'code' => '<?php
                    $a = trim("  abc \n");
                    $b = ltrim("  abc  ");
                    $c = rtrim("  abc  ");
                    $d = chop("abc \n");
                    $e = trim("xxabcxx", "x");
                    $f = trim("abc", "");
                    $g = trim("");
                    $h = trim("\0\x0B\t abc \r");
                    $i = ltrim("0012", "0");

                    /** @param "string"|"int"|"float" $type */
                    function takesAType(string $type): void {}

                    takesAType(trim("string "));',
                'assertions' => [
                    '$a===' => '\'abc\'',
                    '$b===' => '\'abc  \'',
                    '$c===' => '\'  abc\'',
                    '$d===' => '\'abc\'',
                    '$e===' => '\'abc\'',
                    '$f===' => '\'abc\'',
                    '$g===' => '\'\'',
                    '$h===' => '\'abc\'',
                    '$i===' => '\'12\'',
                ],
            ],
            'trimOfALiteralStringIgnoresTheCaseOfTheCall' => [
                'code' => '<?php
                    $a = LTRIM("  abc  ");
                    $b = \\rtrim("  abc  ");
                    $c = Chop("  abc  ");
                    $d = \\TRIM("  abc  ");',
                'assertions' => [
                    '$a===' => '\'abc  \'',
                    '$b===' => '\'  abc\'',
                    '$c===' => '\'  abc\'',
                    '$d===' => '\'abc\'',
                ],
            ],
            'trimThatIsNotOfOneLiteralKeepsTheStubType' => [
                'code' => '<?php
                    /** @var string $s */
                    $s = getenv("X");
                    /** @var "a "|"b " $union */
                    $union = rand(0, 1) ? "a " : "b ";
                    /** @var string $chars */
                    $chars = getenv("Y");

                    $a = trim($s);
                    $b = trim($union);
                    $c = trim("abc ", $chars);
                    $d = trim("abc", "a..c");
                    $e = trim(string: " abc ");
                    $f = trim(...["abc "]);
                    $g = trim(" abc ", " ", "extra");',
                'assertions' => [
                    '$a===' => 'string',
                    '$b===' => 'lowercase-string',
                    '$c===' => 'lowercase-string',
                    '$d===' => 'lowercase-string',
                    '$e===' => 'lowercase-string',
                    '$f===' => 'lowercase-string',
                ],
                'ignored_issues' => ['TooManyArguments'],
            ],
            'strtolowerOfAStringableObjectIsNotRedundant' => [
                'code' => '<?php
                    class Test {
                        public function __toString(): string {
                            return "AAA";
                        }
                    }

                    function lower(Test $test, Stringable $stringable): string {
                        return strtolower($test) . strtolower($stringable);
                    }',
                'assertions' => [],
                'ignored_issues' => ['ImplicitToStringCast'],
                'php_version' => '8.1',
            ],
            'objectWithRefinedToStringReturnMatchesStringParameter' => [
                'code' => '<?php
                    final class LowercaseText {
                        /** @return lowercase-string */
                        public function __toString(): string {
                            return "lowercase";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new LowercaseText());',
                'php_version' => '8.1',
            ],
            'genericObjectWithRefinedToStringReturnMatchesStringParameter' => [
                'code' => '<?php
                    /** @template T of string */
                    final class Box {
                        /** @param T $value */
                        public function __construct(private string $value) {}

                        /** @return T */
                        public function __toString(): string {
                            return $this->value;
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @var Box<lowercase-string> $box */
                    $box = new Box("lowercase");

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase($box);',
                'php_version' => '8.1',
            ],
            'stringConditionOnObjectPreservesObjectType' => [
                'code' => '<?php
                    final class Path {
                        public function __toString(): string {
                            return "path";
                        }

                        public function getElement(): string {
                            return "element";
                        }
                    }

                    function getElement(Path $path): string {
                        if (str_starts_with($path, "p")) {
                            return $path->getElement();
                        }

                        return "";
                    }',
                'ignored_issues' => ['ImplicitToStringCast'],
                'php_version' => '8.1',
            ],
            'failedNestedStringCastDoesNotLeakFromArray' => [
                'code' => '<?php
                    final class Join {
                        public function __toString(): string {
                            return "join";
                        }
                    }

                    /**
                     * @param string|object|list<string>|array{join: array<int|string, object>} $part
                     */
                    function add(string|object|array $part): void {}

                    add(["root" => new Join()]);',
                'ignored_issues' => ['InvalidScalarArgument'],
                'php_version' => '8.1',
            ],
            'strReplaceWithAsManyReplacementsAsSearches' => [
                'code' => '<?php
                    /** @var string $subject */
                    $subject = getenv("X");
                    /** @var list<string> $searches */
                    $searches = [];
                    /** @var list<string> $replacements */
                    $replacements = [];
                    /** @var list{0?: string, 1?: string} $optional */
                    $optional = [];

                    $a = str_replace(["a", "b"], ["x", "y"], $subject);
                    $b = str_replace(["a", "b"], ["x", "y", "z"], $subject);
                    $c = str_replace(["a", "b"], "x", $subject);
                    $d = str_replace(["a", "b"], [], $subject);
                    $e = str_replace($searches, ["x"], $subject);
                    $f = str_replace(["a", "b"], $replacements, $subject);
                    $g = str_replace(["a", "b"], $optional, $subject);
                    $h = str_replace("a", "x", $subject);
                    $i = str_replace(...["a", "x", $subject]);
                    $j = str_replace(subject: $subject, replace: ["x"], search: ["a", "b"]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_on_empty_array' => [
                'code' => '<?php
                    $a = [];
                    if(array_is_list($a)) {
                        //$a is still empty array
                    }
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keeps_keyed_array_shape' => [
                'code' => '<?php
                    /** @param array{0: string, 1: string[]} $arr */
                    function f(array $arr): string {
                        if (!array_is_list($arr)) {
                            throw new Exception();
                        }
                        [$a0, $a1] = $arr;
                        return $a0 . implode("", $a1);
                    }

                    /** @var array{0: string, 1: int} $s */
                    $s = [];
                    assert(array_is_list($s));
                    ',
                'assertions' => [
                    '$s===' => 'list{string, int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keeps_optional_keys_of_keyed_array' => [
                'code' => '<?php
                    /** @var array{0: string, 1?: int} $s */
                    $s = [];
                    assert(array_is_list($s));

                    /** @var array{0?: string, 1: int} $t */
                    $t = [];
                    assert(array_is_list($t));
                    ',
                'assertions' => [
                    '$s===' => 'list{0: string, 1?: int}',
                    '$t===' => 'list{string, int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keeps_callable_keyed_array' => [
                'code' => '<?php
                    /** @var array $c */
                    $c = [];
                    assert(is_callable($c));
                    assert(array_is_list($c));
                    ',
                'assertions' => [
                    '$c===' => 'callable-list{array-key, mixed}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_on_keyed_array_with_extra_keys' => [
                'code' => '<?php
                    /** @var array{0: string, ...<int, bool>} $s */
                    $s = [];
                    assert(array_is_list($s));
                    ',
                'assertions' => [
                    '$s===' => 'non-empty-list<bool|string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'possiblyUndefinedArrayDestructurationOnOptionalArg' => [
                'code' => '<?php
                    class A
                    {
                    }

                    function foo(A $a1, A $a2 = null): void
                    {
                    }

                    $arguments = [new A()];
                    if (mt_rand(1, 10) > 5) {
                        // when this is done outside if - no errors
                        $arguments[] = new A();
                    }

                    foo(...$arguments);
                    ',
            ],
            'is_aWithStringableClass' => [
                'code' => '<?php
                    /**
                     * @psalm-var class-string<Throwable> $exceptionType
                     */
                    if (\is_a(new Exception(), $exceptionType)) {}
                    ',
            ],
            'strposFirstParamAllowClassString' => [
                'code' => '<?php
                    function sayHello(string $needle): void {
                        if (strpos(DateTime::class, $needle) !== false) {}
                    }',
            ],
            'mb_strtolowerProducesStringWithSecondArgument' => [
                'code' => '<?php
                    /** @var non-empty-string $a */
                    $a = "École";
                    $r = mb_strtolower($a, "BASE64");
                    /** @var string $b */
                    $b = "";
                    $s = mb_strtolower($b, "BASE64");
                    $t = mb_strtolower("ABC", "BASE64");
                    $u = mb_strtolower("", "BASE64");
                ',
                'assertions' => [
                    '$r===' => 'non-empty-string',
                    '$s===' => 'string',
                    '$t===' => 'non-empty-string',
                    '$u===' => 'string',
                ],
            ],
            'mb_strtolowerProducesLowercaseStringWithNullOrAbsentEncoding' => [
                'code' => '<?php
                    /** @var non-empty-string $i */
                    $i = "École";
                    /** @var string $j */
                    $j = "";
                    $a = mb_strtolower($i);
                    $b = mb_strtolower($i, null);
                    $c = mb_strtolower($j);
                    $d = mb_strtolower($j, null);
                    $e = mb_strtolower("AAA");
                    $f = mb_strtolower("AAA", null);
                    $g = mb_strtolower("");
                    $h = mb_strtolower("", null);
                ',
                'assertions' => [
                    '$a===' => 'non-empty-lowercase-string',
                    '$b===' => 'non-empty-lowercase-string',
                    '$c===' => 'lowercase-string',
                    '$d===' => 'lowercase-string',
                    '$e===' => 'non-empty-lowercase-string',
                    '$f===' => 'non-empty-lowercase-string',
                    '$g===' => 'lowercase-string',
                    '$h===' => 'lowercase-string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'count_charsProducesArrayOrString' => [
                'code' => '<?php
                    $a = count_chars("foo");
                    $b = count_chars("foo", 1);
                    $c = count_chars("foo", 2);
                    $d = count_chars("foo", 3);
                    $e = count_chars("foo", 4);
                ',
                'assertions' => [
                    '$a===' => 'array<int, int>',
                    '$b===' => 'array<int, int>',
                    '$c===' => 'array<int, int>',
                    '$d===' => 'string',
                    '$e===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'number_formatNamedArgument' => [
                'code' => '<?php
                    echo number_format(10.363, 1, thousands_separator: " ");
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'round_literalValue' => [
                'code' => '<?php
                    $a = round(10.363, 2);
                ',
                'assertions' => [
                    '$a===' => 'float(10.36)',
                ],
            ],
            'allowConstructorAfterEnumExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (enum_exists($s)) {
                            new $s();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
                'php_version' => '8.1',
            ],
            'refineWithEnumExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (enum_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'refineWithClassExistsOrEnumExists' => [
                'code' => '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s) || enum_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function bar(string $s) : void {
                        if (enum_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function baz(string $s) : void {
                        if (enum_exists($s) || interface_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'trimSavesLowercaseAttribute' => [
                'code' => '<?php
                    $a = random_bytes(2);
                    $b = trim(strtolower($a));
                ',
                'assertions' => [
                    '$b===' => 'lowercase-string',
                ],
            ],
            'ltrimSavesLowercaseAttribute' => [
                'code' => '<?php
                    $a = random_bytes(2);
                    $b = ltrim(strtolower($a));
                ',
                'assertions' => [
                    '$b===' => 'lowercase-string',
                ],
            ],
            'rtrimSavesLowercaseAttribute' => [
                'code' => '<?php
                    $a = random_bytes(2);
                    $b = rtrim(strtolower($a));
                ',
                'assertions' => [
                    '$b===' => 'lowercase-string',
                ],
            ],
            'passingStringableObjectToStringableParam' => [
                'code' => '<?php
                    function acceptsStringable(Stringable $_p): void {}
                    /** @param stringable-object $p */
                    function f(object $p): void
                    {
                        f($p);
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'passingStringableToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $_o */
                    function acceptsStringableObject(object $_o): void {}

                    function f(Stringable $o): void
                    {
                        acceptsStringableObject($o);
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'passingImplicitStringableObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    class C { public function __toString(): string { return __CLASS__; }}

                    acceptsStringableObject(new C);
                ',
            ],
            'passingExplicitStringableObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    class C implements Stringable { public function __toString(): string { return __CLASS__; }}

                    acceptsStringableObject(new C);
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'noNeverReturnError' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        if (random_int(0, 1)) {
                            exit;
                        }

                        return "foobar";
                    }
                ',
            ],
            'noNeverReturnErrorOnlyThrows' => [
                'code' => '<?php
                    /**
                     * https://3v4l.org/vCSF4#v8.1.12
                     */
                    function foo(): string {
                         throw new \Exception("foo");
                    }
                ',
            ],
            'noInvalidReturnTypeVoidNeverExplicit' => [
                'code' => '<?php
                    /**
                     * @return void|never
                     */
                    function foo() {
                        if ( rand( 0, 10 ) > 5 ) {
                            exit;
                        }
                    }
                ',
            ],
            'getHeadersAssociativeIn8x' => [
                'code' => '<?php
                    $a = get_headers("https://psalm.dev", true);',
                'assertions' => [
                    '$a' => 'array<string, list<string>|string>|false',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getHeadersAssociativeIn7x' => [
                'code' => '<?php
                    $a = get_headers("https://psalm.dev", 0);',
                'assertions' => [
                    '$a' => 'false|list<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '7.0',
            ],
            'unserializeWithMaxDepthOption' => [
                'code' => '<?php
                    unserialize("", ["allowed_classes" => false, "max_depth" => 1]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'filterVarIpGlobalRangeFlag' => [
                'code' => <<<'PHP'
                    <?php
                    /** @psalm-pure */
                    function isGlobalIp(string $ip): bool
                    {
                        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
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
            'streamSelectDoesNotTakeAContextBeforePhp86' => [
                'code' => '<?php
                    $read = [STDIN];
                    $write = null;
                    $except = null;

                    stream_select($read, $write, $except, 0, 0, null);',
                'error_message' => 'TooManyArguments',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'emptyStringIsNotANonEmptyLowercaseString' => [
                'code' => '<?php
                    /** @param non-empty-lowercase-string $s */
                    function takesNonEmptyLowercase(string $s): void {}

                    takesNonEmptyLowercase("");',
                'error_message' => 'InvalidArgument',
            ],
            'propertyExistsOfADeclaredPropertyIsAlwaysTrue' => [
                'code' => '<?php
                    class Page {
                        public string $title = "";
                    }

                    function check(Page $page): void {
                        if (property_exists($page, "title")) {
                            echo "has title";
                        }
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:29 - Operand of type true is always truthy',
            ],
            'negatedPropertyExistsOfAnInheritedPropertyIsAlwaysFalse' => [
                'code' => '<?php
                    class Page {
                        public string $title = "";
                    }

                    final class Post extends Page {}

                    function check(Post $post): void {
                        if (!property_exists($post, "title")) {
                            echo "no title";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:29 - Operand of type false is always falsy',
            ],
            'functionExistsDoesNotGuardAnotherFunctionInsideAnotherNarrowingCondition' => [
                'code' => '<?php
                    function run(int $arg): void {
                        if ($arg) {
                            if (function_exists("strlen") && unknown_function()) {
                                echo 1;
                            }
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:62 - Function unknown_function does not exist',
            ],
            'functionExistsDoesNotGuardAnotherFunctionInTheSameCondition' => [
                'code' => '<?php
                    function run(): void {
                        if (function_exists("strlen") && unknown_function()) {
                            echo 1;
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:58 - Function unknown_function does not exist',
            ],
            'functionExistsDoesNotGuardAnotherFunctionInItsBody' => [
                'code' => '<?php
                    function run(): void {
                        if (function_exists("guarded_function")) {
                            guarded_function();
                            unknown_function();
                        }
                    }',
                'error_message' => 'UndefinedFunction - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:29 - Function unknown_function does not exist',
            ],
            'functionExistsDoesNotGuardAnotherCallableString' => [
                'code' => '<?php
                    function run(): void {
                        if (function_exists("guarded_callback")) {
                            array_map("unknown_callback", [1]);
                        }
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'builtInByReferenceParameterRejectsAConstant' => [
                'code' => '<?php
                    const NUMBERS = [3, 1, 2];
                    sort(NUMBERS);',
                'error_message' => 'InvalidPassByReference - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Parameter 1 of sort expects a variable',
            ],
            'mysqliBindResultRequiresOutputVariable' => [
                'code' => '<?php
                    function bind(mysqli_stmt $statement): void {
                        mysqli_stmt_bind_result($statement);
                    }',
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidScalarArgument' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidArgumentCallableWithoutArgsUnion' => [
                'code' => '<?php
                    function foo(int $a): void {}

                    /**
                     * @param callable()|float $callable
                     * @return void
                     */
                    function acme($callable) {}
                    acme("foo");',
                'error_message' => 'InvalidArgument',
            ],
            'invalidArgumentWithDeclareStrictTypes' => [
                'code' => '<?php declare(strict_types=1);
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidArgument',
            ],
            'invalidArgumentFalseTrueExpected' => [
                'code' => '<?php
                    /**
                     * @param true|string $arg
                     * @return void
                     */
                    function foo($arg) {}

                    foo(false);',
                'error_message' => 'InvalidArgument',
            ],
            'builtinFunctioninvalidArgumentWithWeakTypes' => [
                'code' => '<?php
                    $s = substr(5, 4);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'builtinFunctioninvalidArgumentWithDeclareStrictTypes' => [
                'code' => '<?php declare(strict_types=1);
                    $s = substr(5, 4);',
                'error_message' => 'InvalidArgument',
            ],
            'builtinFunctioninvalidArgumentWithDeclareStrictTypesInClass' => [
                'code' => '<?php declare(strict_types=1);
                    class A {
                        public function foo() : void {
                            $s = substr(5, 4);
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'clearIssetContext' => [
                'code' => '<?php
                    function greet(bool $arg): ?string
                    {
                        return $arg ? "hi" : null;
                    }

                    echo greet($undef) ?? "bye";',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'mixedArgument' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    /** @var mixed */
                    $a = "hello";
                    fooFoo($a);',
                'error_message' => 'MixedArgument',
                'error_levels' => ['MixedAssignment'],
            ],
            'nullArgument' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo(null);',
                'error_message' => 'NullArgument',
            ],
            'tooFewArguments' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'tooManyArguments' => [
                'code' => '<?php
                    function fooFoo(int $a): void {}
                    fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - Too many arguments for fooFoo '
                    . '- expecting 1 but saw 2',
            ],
            'tooManyArgumentsForConstructor' => [
                'code' => '<?php
                  class A { }
                  new A("hello");',
                'error_message' => 'TooManyArguments',
            ],
            'typeCoercion' => [
                'code' => '<?php
                    class A {}
                    class B extends A{}

                    function fooFoo(B $b): void {}
                    fooFoo(new A());',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'arrayTypeCoercion' => [
                'code' => '<?php
                    class A {}
                    class B extends A{}

                    /**
                     * @param  B[]  $b
                     * @return void
                     */
                    function fooFoo(array $b) {}
                    fooFoo([new A()]);',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'duplicateParam' => [
                'code' => '<?php
                    /**
                     * @return void
                     */
                    function f($p, $p) {}',
                'error_message' => 'DuplicateParam',
                'error_levels' => ['MissingParamType'],
            ],
            'invalidParamDefault' => [
                'code' => '<?php
                    function f(int $p = false) {}',
                'error_message' => 'InvalidParamDefault',
            ],
            'invalidParamDefaultNewObjectNotImplementingInterface' => [
                'code' => '<?php
                    function f(Traversable $p = new stdClass()): void {}',
                'error_message' => 'InvalidParamDefault',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidParamDefaultNewObjectNotInUnion' => [
                'code' => '<?php
                    function f(Countable|int $p = new stdClass()): void {}',
                'error_message' => 'InvalidParamDefault',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidDocblockParamDefault' => [
                'code' => '<?php
                    /**
                     * @param  int $p
                     * @return void
                     */
                    function f($p = false) {}',
                'error_message' => 'InvalidParamDefault',
            ],
            'badByRef' => [
                'code' => '<?php
                    function fooFoo(string &$v): void {}
                    fooFoo("a");',
                'error_message' => 'InvalidPassByReference',
            ],
            'badArrayByRef' => [
                'code' => '<?php
                    function fooFoo(array &$a): void {}
                    fooFoo([1, 2, 3]);',
                'error_message' => 'InvalidPassByReference',
            ],
            'invalidArgAfterCallable' => [
                'code' => '<?php
                    /**
                     * @param callable $callback
                     * @return void
                     */
                    function route($callback) {
                      if (!is_callable($callback)) {  }
                      takes_int("string");
                    }

                    function takes_int(int $i) {}',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'objectToStringReturnDoesNotMatchLowercaseStringParameter' => [
                'code' => '<?php
                    final class UppercaseText {
                        /** @return "AAA" */
                        public function __toString(): string {
                            return "AAA";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new UppercaseText());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercesToLowercaseStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercionReportsImplicitCast' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ArgumentTypeCoercion */
                    acceptsLowercase(new Text());',
                'error_message' => 'ImplicitToStringCast',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'stringableUnionWithNonStringableObjectDoesNotCoerce' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    final class Other {}

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @param Text|Other $value */
                    function passObject($value): void {
                        /** @psalm-suppress ImplicitToStringCast */
                        acceptsLowercase($value);
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'untypedObjectToStringReturnCoercesToLowercaseStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString() {
                            return "text";
                        }
                    }

                    /** @param lowercase-string $value */
                    function acceptsLowercase(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsLowercase(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'objectToStringReturnCoercesToClassStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param class-string $value */
                    function acceptsClassString(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsClassString(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercesToNumericStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param numeric-string $value */
                    function acceptsNumericString(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsNumericString(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnDoesNotMatchLiteralStringParameter' => [
                'code' => '<?php
                    final class Text {
                        /** @return "AAA" */
                        public function __toString(): string {
                            return "AAA";
                        }
                    }

                    /** @param "BBB"|"CCC" $value */
                    function acceptsOtherLiterals(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsOtherLiterals(new Text());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnDoesNotMatchNonEmptyStringParameter' => [
                'code' => '<?php
                    final class EmptyText {
                        /** @return "" */
                        public function __toString(): string {
                            return "";
                        }
                    }

                    /** @param non-empty-string $value */
                    function acceptsNonEmpty(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsNonEmpty(new EmptyText());',
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectToStringReturnCoercesToNonEmptyStringParameter' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @param non-empty-string $value */
                    function acceptsNonEmpty(string $value): void {}

                    /** @psalm-suppress ImplicitToStringCast */
                    acceptsNonEmpty(new Text());',
                'error_message' => 'ArgumentTypeCoercion',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'clampIsUndefinedBeforePhp86' => [
                'code' => '<?php
                    $result = clamp(5, 0, 10);',
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'undefinedFunctionInArrayMap' => [
                'code' => '<?php
                    array_map(
                        "undefined_function",
                        [1, 2, 3]
                    );',
                'error_message' => 'UndefinedFunction',
            ],
            'objectLikeKeyChecksAgainstDifferentGeneric' => [
                'code' => '<?php
                    /**
                     * @param array<string, int> $b
                     */
                    function a($b): int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'objectLikeKeyChecksAgainstDifferentTKeyedArray' => [
                'code' => '<?php
                    /**
                     * @param array{a: int} $b
                     */
                    function a($b): int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyNullFunctionCall' => [
                'code' => '<?php
                    $a = rand(0, 1) ? function(): void {} : null;
                    $a();',
                'error_message' => 'PossiblyNullFunctionCall',
            ],
            'possiblyInvalidFunctionCall' => [
                'code' => '<?php
                    $a = rand(0, 1) ? function(): void {} : 23515;
                    $a();',
                'error_message' => 'PossiblyInvalidFunctionCall',
            ],
            'varExportAssignmentToVoid' => [
                'code' => '<?php
                    $a = var_export(["a"]);',
                'error_message' => 'AssignmentToVoid',
            ],
            'explodeWithEmptyString' => [
                'code' => '<?php
                    function exploder(string $s) : array {
                        return explode("", $s);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'complainAboutArrayToIterable' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    /**
                     * @param iterable<mixed,A> $p
                     */
                    function takesIterableOfA(iterable $p): void {}

                    takesIterableOfA([new B]); // should complain',
                'error_message' => 'InvalidArgument',
            ],
            'complainAboutArrayToIterableSingleParam' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    /**
                     * @param iterable<A> $p
                     */
                    function takesIterableOfA(iterable $p): void {}

                    takesIterableOfA([new B]); // should complain',
                'error_message' => 'InvalidArgument',
            ],
            'putInvalidTypeMessagesFirst' => [
                'code' => '<?php
                    $q = rand(0,1) ? new stdClass : false;
                    strlen($q);',
                'error_message' => 'InvalidArgument',
            ],
            'getTypeInvalidValue' => [
                'code' => '<?php
                    /**
                     * @param mixed $maybe
                     */
                    function matchesTypes($maybe) : void {
                        $t = gettype($maybe);
                        if ($t === "bool") {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'rangeWithFloatStep' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, .3) as $x) {
                        foo($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'rangeWithFloatStart' => [
                'code' => '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1.4, 10) as $x) {
                        foo($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'duplicateFunction' => [
                'code' => '<?php
                    function f() : void {}
                    function f() : void {}',
                'error_message' => 'DuplicateFunction',
            ],
            'duplicateCoreFunction' => [
                'code' => '<?php
                    function sort() : void {}',
                'error_message' => 'DuplicateFunction',
            ],
            'functionCallOnMixed' => [
                'code' => '<?php
                    /**
                     * @var mixed $s
                     * @psalm-suppress MixedAssignment
                     */
                    $s = 1;
                    $s();',
                'error_message' => 'MixedFunctionCall',
            ],
            'iterableOfObjectCannotAcceptIterableOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return iterable<int,int> */
                    function iterable() { yield 1; }

                    accepts(iterable());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptTraversableOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return Traversable<int,int> */
                    function traversable() { yield 1; }

                    accepts(traversable());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptGeneratorOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return Generator<int,int,mixed,void> */
                    function generator() { yield 1; }

                    accepts(generator());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptArrayOfInt' => [
                'code' => '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return array<int,int> */
                    function arr() { return [1]; }

                    accepts(arr());',
                'error_message' => 'InvalidArgument',
            ],
            'nonNullableByRef' => [
                'code' => '<?php
                    function foo(string &$s) : void {}

                    function bar() : void {
                        foo($bar);
                    }',
                'error_message' => 'NullReference',
            ],
            'intCastByRef' => [
                'code' => '<?php
                    function foo(int &$i) : void {}

                    $a = rand(0, 1) ? null : 5;
                    /** @psalm-suppress MixedArgument */
                    foo((int) $a);',
                'error_message' => 'InvalidPassByReference',
            ],
            'implicitAssignmentToStringFromMixed' => [
                'code' => '<?php
                    /** @param "a"|"b" $s */
                    function takesString(string $s) : void {}
                    function takesInt(int $i) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesString($s);
                        takesInt($s);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'tooFewArgsAccurateCount' => [
                'code' => '<?php
                    preg_match(\'/adsf/\');',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - Too few arguments for preg_match - expecting subject to be passed',
            ],
            'tooFewArgsForCallableNamesTheCallable' => [
                'code' => '<?php
                    /** @param callable(mixed): bool $c */
                    function f(callable $c): void {
                        $c();
                    }',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too few arguments for callable(mixed) - expecting parameter #1 to be passed',
            ],
            'tooFewArgsForCallableCountsTheMissingParameterFromTheStart' => [
                'code' => '<?php
                    /** @param callable(int, string): void $c */
                    function f(callable $c): void {
                        $c(1);
                    }',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too few arguments for callable(int, string) - expecting parameter #2 to be passed',
            ],
            'tooFewArgsForCallableWithNamedParametersNamesTheParameter' => [
                'code' => '<?php
                    /** @param callable(int $a, string $b): void $c */
                    function f(callable $c): void {
                        $c(1);
                    }',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too few arguments for callable(int, string) - expecting b to be passed',
            ],
            'tooManyArgsForCallableNamesTheCallable' => [
                'code' => '<?php
                    /** @param callable(int, string): void $c */
                    function f(callable $c): void {
                        $c(1, "a", 3);
                    }',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:25 - Too many arguments for callable(int, string) - expecting 2 but saw 3',
            ],
            'compactUndefinedVariable' => [
                'code' => '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function foo() : array {
                        return compact("a", "b", "c");
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'countCallableArrayShouldBeTwo' => [
                'code' => '<?php
                    /** @param callable|false $x */
                    function example($x) : void {
                        if (is_array($x)) {
                            $c = count($x);
                            if ($c !== 2) {}
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'countOnUnknownObjectCannotBePure' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function example(\Countable $x) : int {
                        return count($x);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'coerceCallMapArgsInStrictMode' => [
                'code' => '<?php
                    declare(strict_types=1);

                    function getStr() : ?string {
                        return rand(0,1) ? "test" : null;
                    }

                    function test() : void {
                        $g = getStr();
                        /** @psalm-suppress PossiblyNullArgument */
                        $x = strtoupper($g);
                        $c = "prefix " . (strtoupper($g ?? "") === "x" ? "xa" : "ya");
                        echo "$x, $c\n";
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'noCrashOnEmptyArrayPush' => [
                'code' => '<?php
                    array_push();',
                'error_message' => 'TooFewArguments',
            ],
            'printOnlyString' => [
                'code' => '<?php
                    print [];',
                'error_message' => 'InvalidArgument',
            ],
            'printReturns1' => [
                'code' => '<?php
                    (print "test") === 2;',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'sodiumMemzeroNullifyString' => [
                'code' => '<?php
                    function returnsStr(): string {
                        $str = "x";
                        sodium_memzero($str);
                        return $str;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'noCrashWithPattern' => [
                'code' => '<?php
                    echo !\is_callable($loop_callback)
                        || (\is_array($loop_callback)
                            && !\method_exists(...$loop_callback));',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'parseUrlPossiblyUndefined' => [
                'code' => '<?php
                    function bar(string $s) : string {
                        $parsed = parse_url($s);

                        return $parsed["host"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'parseUrlPossiblyUndefined2' => [
                'code' => '<?php
                    function bag(string $s) : string {
                        $parsed = parse_url($s);

                        if (is_string($parsed["host"] ?? false)) {
                            return $parsed["host"];
                        }

                        return "";
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'strposNoSetFirstParam' => [
                'code' => '<?php
                    function sayHello(string $format): void {
                        if (strpos("u", $format)) {}
                    }',
                'error_message' => 'InvalidLiteralArgument',
            ],
            'strposMultibyteLiteralFirstParam' => [
                'code' => '<?php
                    function sayHello(string $format): void {
                        if (strpos("здравствуй мир", $format)) {}
                    }',
                'error_message' => 'InvalidLiteralArgument',
            ],
            'curlInitIsResourceFailsIn8x' => [
                'code' => '<?php
                    $ch = curl_init();
                    if (!is_resource($ch)) {}',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'maxCallWithArray' => [
                'code' => '<?php
                    function foo(array $a) {
                        max($a);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'pregSplitNoEmpty' => [
                'code' => '<?php
                    /** @return non-empty-list */
                    function foo(string $s) {
                        return preg_split("/ /", $s, -1, PREG_SPLIT_NO_EMPTY);
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'maxWithMixed' => [
                'code' => '<?php
                    /** @var mixed $b */;
                    /** @var mixed $c */;
                    $a = max($b, $c);',
                'error_message' => 'MixedAssignment',
            ],
            'literalFalseArgument' => [
                'code' => '<?php
                    function takesAString(string $s): void{
                        echo $s;
                    }

                    takesAString(false);',
                'error_message' => 'InvalidArgument',
            ],
            'getClassWithoutArgsOutsideClass' => [
                'code' => '<?php

                    echo get_class();',
                'error_message' => 'TooFewArguments',
            ],
            'count_charsWithInvalidMode' => [
                'code' => '<?php
                    function scope(int $mode){
                        $a = count_chars("foo", $mode);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'getObjectVarsOfAStdClassIsAMixedCoercion' => [
                'code' => '<?php
                    /** @param array{i: int} $array */
                    function takesAnArray(array $array): void {
                        echo $array["i"];
                    }

                    $obj = new stdClass();

                    takesAnArray(get_object_vars($obj));',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'strReplaceWithFewerReplacementsThanSearches' => [
                'code' => '<?php
                    $subject = "Hello";
                    $search = ["a", "b", "c"];
                    $replace = ["x", "y"];
                    echo str_replace($search, $replace, $subject);',
                'error_message' => 'InvalidArgument',
            ],
            'strIreplaceWithFewerReplacementsThanSearches' => [
                'code' => '<?php
                    echo str_ireplace(["a", "b"], ["x"], "Hello");',
                'error_message' => 'InvalidArgument',
            ],
            'array_is_list_literal_array' => [
                'code' => '<?php
                    $list = [1 => 0, 0 => 1];
                    assert(array_is_list($list));',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_on_list_shape' => [
                'code' => '<?php
                    /** @param list{string, int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keyed_array_with_string_keys' => [
                'code' => '<?php
                    /** @param array{a: string, b: int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keyed_array_without_first_key' => [
                'code' => '<?php
                    /** @param array{1: string, 2: int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'array_is_list_keyed_array_with_required_key_after_gap' => [
                'code' => '<?php
                    /** @param array{0: string, 2: int} $arr */
                    function f(array $arr): void {
                        assert(array_is_list($arr));
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'keyed_array_asserted_as_list_of_other_values' => [
                'code' => '<?php
                    /**
                     * @param mixed $value
                     * @psalm-assert list<int> $value
                     */
                    function assertIntList($value): void {}

                    /** @param array{0: string} $arr */
                    function f(array $arr): void {
                        assertIntList($arr);
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'passingObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    acceptsStringableObject((object)[]);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'passingNonStringableObjectToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    class C {}
                    acceptsStringableObject(new C);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'passingStdClassToStringableObjectParam' => [
                'code' => '<?php
                    /** @param stringable-object $o */
                    function acceptsStringableObject(object $o): void {}

                    acceptsStringableObject(new stdClass);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'shouldReturnNeverNotString' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function finalFunc() {
                        exit;
                    }

                    finalFunc();',
                'error_message' => 'InvalidReturnType',
            ],
            'shouldReturnNeverNotStringCaller' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                       finalFunc();
                    }

                    /**
                     * @return never
                     */
                    function finalFunc() {
                        exit;
                    }

                    foo();',
                'error_message' => 'InvalidReturnType',
            ],
            'shouldReturnNeverNotStringNoDocblockCaller' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                       finalFunc();
                    }

                    function finalFunc() {
                        exit;
                    }

                    foo();',
                'error_message' => 'InvalidReturnType',
            ],
            'DontAcceptArrayWithShapesNotContained' => [
                'code' => '<?php

                    /** @param array{bar: 0|positive-int} $foo */
                    function takesArrayShapeWithZeroOrPositiveInt(array $foo): void
                    {
                    }

                    /** @var int $mayBeInt */
                    $mayBeInt = -1;

                    takesArrayShapeWithZeroOrPositiveInt(["bar" => $mayBeInt]);
                ',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'strtolowerOfALowercaseString' => [
                'code' => '<?php
                    echo strtolower("abc");',
                'error_message' => 'The call to strtolower is unnecessary',
            ],
            'is_a_withAStringAndNoThirdArg' => [
                'code' => '<?php
                    is_a("Foo", Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAStringAndFalseThirdArg' => [
                'code' => '<?php
                    is_a("Foo", Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAUnionOfStringsAndNoThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? "Foo" : "Bar", Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAUnionOfStringsAndFalseThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? "Foo" : "Bar", Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAClassStringAndNoThirdArg' => [
                'code' => '<?php
                    is_a(InvalidArgumentException::class, Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAClassStringAndFalseThirdArg' => [
                'code' => '<?php
                    is_a(InvalidArgumentException::class, Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAUnionOfClassStringsAndNoThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? InvalidArgumentException::class : RuntimeException::class, Exception::class);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'is_a_withAUnionOfClassStringsAndFalseThirdArg' => [
                'code' => '<?php
                    is_a(rand(0, 1) ? InvalidArgumentException::class : RuntimeException::class, Exception::class, false);
                ',
                'error_message' => 'RedundantFunctionCall',
            ],
            'incorrectCallableParamDefault' => [
                'code' => '<?php
                    function foo(callable $_a = "strlen"): void {}
                ',
                'error_message' => 'InvalidParamDefault',
            ],
            'disallowStrposIntSecondParam' => [
                'code' => '<?php
                    function hasZeroByteOffset(string $s) : bool {
                        return strpos($s, 0) !== false;
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'disallowNeverTypeForParam' => [
                'code' => '<?php
                    function foo(never $_): void
                    {
                        return;
                    }
                    ',
                'error_message' => 'ParadoxicalCondition',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'extractVarCheckInvalid' => [
                'code' => '<?php
                    function takesInt(int $i): void {}

                    $foo = "123hello";
                    $a = [$foo => 15];
                    extract($a);
                    takesInt($foo);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'unserializeWithInvalidMaxDepthType' => [
                'code' => '<?php
                    unserialize("", ["max_depth" => "foo"]);',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
        ];
    }

    public function testTriggerErrorDefault(): void
    {
        $config = Config::getInstance();
        $config->trigger_error_exits = 'default';

        $this->addFile(
            'somefile.php',
            '<?php
                /** @return true */
                function returnsTrue(): bool {
                    return trigger_error("", E_USER_NOTICE);
                }
                /** @return never */
                function returnsNever(): void {
                    trigger_error("", E_USER_ERROR);
                }
                /**
                 * @psalm-suppress ArgumentTypeCoercion
                 * @return mixed
                 */
                function returnsNeverOrBool(int $i) {
                    return trigger_error("", $i);
                }',
        );

        //will only pass if no exception is thrown
        $this->assertTrue(true);

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTriggerErrorAlways(): void
    {
        $config = Config::getInstance();
        $config->trigger_error_exits = 'always';

        $this->addFile(
            'somefile.php',
            '<?php
                /** @return never */
                function returnsNever1(): void {
                    trigger_error("", E_USER_NOTICE);
                }
                /** @return never */
                function returnsNever2(): void {
                    trigger_error("", E_USER_ERROR);
                }',
        );

        //will only pass if no exception is thrown
        $this->assertTrue(true);

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTriggerErrorNever(): void
    {
        $config = Config::getInstance();
        $config->trigger_error_exits = 'never';

        $this->addFile(
            'somefile.php',
            '<?php
                /** @return true */
                function returnsTrue1(): bool {
                    return trigger_error("", E_USER_NOTICE);
                }
                /** @return true */
                function returnsTrue2(): bool {
                    return trigger_error("", E_USER_ERROR);
                }',
        );

        //will only pass if no exception is thrown
        $this->assertTrue(true);

        $this->analyzeFile('somefile.php', new Context());
    }
}
