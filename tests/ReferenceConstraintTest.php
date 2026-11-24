<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ReferenceConstraintTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testReferenceToPropertyForgottenAfterCallHoldsItsDeclaredType(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('NullableReturnStatement');

        $this->addFile(
            'somefile.php',
            '<?php
                final class A {
                    public ?int $n = null;
                }

                function writeAnything(): void {}

                function f(A $a): int {
                    $a->n = 5;
                    $n = &$a->n;
                    writeAnything();
                    return $n;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'foreachByReferenceOverAPropertyMayWriteItsItemType' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public array $ints = [];

                        /** @param list<string> $strings */
                        public function f(array $strings): array {
                            foreach ($this->ints as &$v) {
                                $v = $v + 1;
                            }
                            foreach ($strings as &$v) {
                                $v = "s";
                            }
                            return $strings;
                        }
                    }',
            ],
            'paramOutOfAByRefParamWhoseTemplateItsOwnArgumentBindsIsNotHandledAgain' => [
                'code' => '<?php
                    function run(string $command): string {
                        exec($command, $output, $exitCode);

                        return implode(" ", $output) . $exitCode;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'paramOutOfAByRefParamBeforeTheArgumentThatBindsItsTemplate' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function before(mixed &$matches = [], int $flags = 0): void {}

                    before($m, 256);',
                'assertions' => [
                    '$m===' => "'M256'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamOfAStaticMethodBeforeTheArgumentThatBindsItsTemplate' => [
                'code' => '<?php
                    final class C {
                        /**
                         * @template T
                         * @param mixed $out
                         * @param T $v
                         * @param-out list<T> $out
                         */
                        public static function s(&$out, $v): void {
                            $out = [$v];
                        }

                        /**
                         * @template T
                         * @param mixed $out
                         * @param T $v
                         * @param-out list<T> $out
                         */
                        public function m(&$out, $v): void {
                            $out = [$v];
                        }
                    }

                    C::s($static, "x");
                    (new C())->m($instance, "x");',
                'assertions' => [
                    '$static===' => "list<'x'>",
                    '$instance===' => "list<'x'>",
                ],
            ],
            'paramOutOfAByRefParamAfterTheArgumentThatBindsItsTemplate' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function after(int $flags, mixed &$matches = []): void {}

                    after(0, $m);',
                'assertions' => [
                    '$m===' => "'M0'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamBeforeAnArgumentGivenByName' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function before(mixed &$matches = [], int $flags = 0): void {}

                    before(matches: $m, flags: 256);',
                'assertions' => [
                    '$m===' => "'M256'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamBeforeAnUnpackedArgument' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function before(mixed &$matches = [], int $flags = 0): void {}

                    before($m, ...[256]);',
                'assertions' => [
                    '$m===' => "'M256'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamBeforeANestedMaskArgument' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int-mask<256, 512>
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : (TFlags is 768 ? "M768" : "M0")) $matches
                     */
                    function pmatch(mixed &$matches = [], int $flags = 0): void {}

                    pmatch($m, 256);
                    pmatch($n, 768);',
                'assertions' => [
                    '$m===' => "'M256'",
                    '$n===' => "'M768'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfTwoByRefParamsBeforeTheArgumentsThatBindThem' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template U
                     * @param T $a
                     * @param U $b
                     * @param-out T $x
                     * @param-out U $y
                     */
                    function two(mixed &$x, mixed &$y, mixed $a, mixed $b): void {}

                    two($x, $y, 1, "s");',
                'assertions' => [
                    '$x===' => '1',
                    '$y===' => "'s'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamBetweenTheArgumentsThatBindItsTemplates' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template U
                     * @param T $a
                     * @param U $b
                     * @param-out T|U $r
                     */
                    function mid(mixed $a, mixed &$r, mixed $b): void {}

                    mid(1, $r, "s");',
                'assertions' => [
                    '$r===' => "'s'|1",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamWithTheTemplateOfALaterArgumentInATypeArgument' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $a
                     * @param-out list<T> $r
                     */
                    function wrap(mixed &$r, mixed $a): void {}

                    wrap($r, 5);',
                'assertions' => [
                    '$r===' => 'list<5>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamWithAValueOfTheTemplateOfALaterArgument' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $a
                     * @param-out value-of<T> $r
                     */
                    function vo(mixed &$r, array $a): void {}

                    vo($r, ["x" => 1, "y" => 2]);',
                'assertions' => [
                    '$r===' => '1|2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefParamBeforeAClosureThatBindsItsTemplate' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param callable(): T $f
                     * @param-out T $r
                     */
                    function viaClosure(mixed &$r, callable $f): void {}

                    viaClosure($r, fn() => 42);',
                'assertions' => [
                    '$r===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefPropertyBeforeTheArgumentThatBindsItsTemplate' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function before(mixed &$matches = [], int $flags = 0): void {}

                    final class Holder {
                        public mixed $p = null;

                        public function go(): void {
                            before($this->p, 256);
                            /** @psalm-check-type-exact $this->p = \'M256\' */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAByRefArrayElementBeforeTheArgumentThatBindsItsTemplate' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function before(mixed &$matches = [], int $flags = 0): void {}

                    $arr = [];
                    before($arr["k"], 256);',
                'assertions' => [
                    '$arr===' => "array{k: 'M256'}",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAClassTemplateReplacesAMixedVariable' => [
                'code' => '<?php
                    /** @template T of int */
                    class FooBar {
                        /** @param-out T $s */
                        public function fill(mixed &$s): void {}
                    }

                    /** @param mixed $value */
                    function foo($value): int {
                        $fooBar = new FooBar();
                        $fooBar->fill($value);

                        return $value;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOfAFunctionTemplateIsWhatTheArgumentsBoundIt' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $in
                     * @param-out T $out
                     */
                    function same(mixed $in, mixed &$out): void {
                        $out = $in;
                    }

                    $a = 5;
                    same("x", $a);',
                'assertions' => [
                    '$a===' => "'x'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'paramOutOnInterfaceMethodWithoutBody' => [
                'code' => '<?php
                    interface I {
                        /** @param-out int $a */
                        public function a(?int &$a = null): void;

                        /** @param-out array{foo: string, ...} $a */
                        public function addFoo(array &$a): void;
                    }',
            ],
            'paramOutOnAbstractMethodWithoutBody' => [
                'code' => '<?php
                    abstract class A {
                        /** @param-out int $a */
                        abstract public function a(?int &$a = null): void;
                    }',
            ],
            'functionParameterNoViolation' => [
                'code' => '<?php
                    /** @return void */
                    function changeInt(int &$a) {
                      $a = 5;
                    }',
            ],
            'dontAllowByRefVarToBeAltered' => [
                'code' => '<?php
                    /**
                     * @param ?string $str
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function nullable_ref_modifier(&$str): void {
                        if (strlen($str) > 5) {
                            $str = null;
                        }
                    }',
            ],
            'trackFunctionReturnRefs' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "bar";

                        public function &getString() : string {
                            return $this->foo;
                        }
                    }

                    function useString(string &$s) : void {}
                    $a = new A();

                    useString($a->getString());',
            ],
            'assignByRefToMixed' => [
                'code' => '<?php
                    function testRef() : array {
                        $result = [];
                        foreach ([1, 2, 1] as $v) {
                            $x = &$result;
                            if (!isset($x[$v])) {
                                $x[$v] = 0;
                            }
                            $x[$v] ++;
                        }
                        return $result;
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                    'MixedReturnStatement',
                    'MixedOperand',
                ],
            ],
            'paramOutOnVariadicParam' => [
                'code' => '<?php
                    /**
                     * @param-out int ...$refs
                     */
                    function setAll(string &...$refs): void {
                        foreach ($refs as &$ref) {
                            $ref = 1;
                        }
                    }

                    $a = "x";
                    setAll($a);',
                'assertions' => ['$a' => 'int'],
            ],
            'paramOutRefineType' => [
                'code' => '<?php
                    /**
                     * @param-out string $s
                     */
                    function addFoo(?string &$s) : void {
                        if ($s === null) {
                            $s = "hello";
                        }
                        $s .= "foo";
                    }

                    addFoo($a);

                    echo strlen($a);',
            ],
            'paramOutChangeType' => [
                'code' => '<?php
                    /**
                     * @param-out int $s
                     */
                    function addFoo(?string &$s) : void {
                        if ($s === null) {
                            $s = 5;
                            return;
                        }
                        $s = 4;
                    }

                    addFoo($a);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'paramOutWithPurity' => [
                'code' => '<?php
                    /**
                     * @param-out Closure[pure](): int $f
                     */
                    function makeClosure(?Closure &$f) : void {
                        $f = fn(): int => 1;
                    }

                    makeClosure($a);',
                'assertions' => [
                    '$a' => 'Closure[pure]():int',
                ],
            ],
            'paramOutReturn' => [
                'code' => '<?php
                    /**
                     * @param-out bool $s
                     */
                    function foo(?bool &$s) : void {
                        $s = true;
                    }

                    $b = false;
                    foo($b);',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'dontChangeThis' => [
                'code' => '<?php
                    interface I {}
                    class C implements I {
                        public function foo() : self {
                            bar($this);
                            return $this;
                        }
                    }

                    function bar(I &$i) : void {}',
            ],
            'notEmptyArrayAccess' => [
                'code' => '<?php
                    /**
                     * @param mixed $value
                     * @param-out int $value
                     */
                    function addValue(&$value) : void {
                        $value = 5;
                    }

                    $foo = [];

                    addValue($foo["a"]);',
            ],
            'paramOutArrayDefaultNullWithThrow' => [
                'code' => '<?php
                    /**
                     * @param-out array{errors: int}|null $info
                     */
                    function idnToAsci(?array &$info = null): void {
                        if (rand(0, 1)) {
                            $info = null;
                        }

                        throw new \UnexpectedValueException();
                    }',
            ],
            'specificArrayWalkBehavior' => [
                'code' => '<?php
                    function withArrayWalk(array &$val): void {
                        array_walk($val, /** @param mixed $arg */ function (&$arg): void {});
                    }
                    function withArrayWalkRecursive(array &$val): void {
                        array_walk_recursive($val, /** @param mixed $arg */ function (&$arg): void {});
                    }',
            ],
            'investigateByrefArg' => [
                'code' => '<?php
                    class A {}
                    function takesNullableObj(?A &$a): bool { return true; }

                    $a = null;

                    if (takesNullableObj($a) === false) {
                        return;
                    } else {}

                    if ($a) {}',
            ],
            'PHP80-paramOutChangeTypeWithNamedArgument' => [
                'code' => '<?php
                    /**
                     * @param-out int $s
                     */
                    function addFoo(bool $five = true, ?string &$s = null) : void {
                        if ($five) {
                            $s = 5;
                            return;
                        }
                        $s = 4;
                    }

                    addFoo(s: $a);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'paramOutSelfResolvesToDeclaringClass' => [
                'code' => '<?php
                    namespace App;

                    class A {
                        public function name(): string {
                            return "a";
                        }
                    }

                    class B extends A {
                        /** @param-out self $out */
                        public function setSelf(?self &$out): void {
                            $out = $this;
                        }

                        /** @param-out self $out */
                        public static function setSelfStatic(?self &$out): void {
                            $out = new self();
                        }
                    }

                    $b = new B();

                    $direct = null;
                    $b->setSelf($direct);

                    $setSelf = $b->setSelf(...);
                    $viaClosure = null;
                    $setSelf($viaClosure);
                    $name = $viaClosure->name();

                    $setSelfStatic = B::setSelfStatic(...);
                    $viaStaticClosure = null;
                    $setSelfStatic($viaStaticClosure);

                    $fromCallable = \Closure::fromCallable([$b, "setSelf"]);
                    $viaFromCallable = null;
                    $fromCallable($viaFromCallable);',
                'assertions' => [
                    '$direct' => 'App\\B',
                    '$viaClosure' => 'App\\B',
                    '$name' => 'string',
                    '$viaStaticClosure' => 'App\\B',
                    '$viaFromCallable' => 'App\\B',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'paramOutParentResolvesToParentClass' => [
                'code' => '<?php
                    namespace App;

                    class A {}

                    class B extends A {
                        /** @param-out parent $out */
                        public function setParent(?A &$out): void {
                            $out = new A();
                        }
                    }

                    $parent = null;
                    (new B())->setParent($parent);',
                'assertions' => [
                    '$parent' => 'App\\A',
                ],
            ],
            'referenceToPropertyAcceptsItsDeclaredType' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                        public ?int $n = 0;
                        /** @var array{a: int} */
                        public array $shape = ["a" => 1];
                        /** @var array<string, int|null> */
                        public array $map = [];
                        public $untyped;
                        public static int $s = 0;
                    }

                    $a = new A();
                    $i = &$a->i;
                    $i = 5;
                    $n = &$a->n;
                    $n = null;
                    $shape_item = &$a->shape["a"];
                    $shape_item = 2;
                    $map_item = &$a->map["x"];
                    $map_item = 3;
                    $untyped = &$a->untyped;
                    $untyped = "anything";
                    $s = &A::$s;
                    $s = 6;',
                'assertions' => [
                    '$i===' => '5',
                    '$shape_item===' => '2',
                ],
                'ignored_issues' => ['MissingPropertyType'],
            ],
            'referenceToPropertyReboundInALoopIsNoLongerConstrainedAfterIt' => [
                'code' => '<?php
                    final class A {
                        /** @var array<string, mixed> */
                        public array $data = [];

                        /** @param non-empty-list<string> $path */
                        public function set(array $path, string $value): void {
                            $ref = &$this->data;
                            foreach ($path as $key) {
                                if (!is_array($ref)) {
                                    $ref = [];
                                }
                                $ref = &$ref[$key];
                            }
                            $ref = $value;
                        }
                    }',
            ],
            'referenceToPropertyReboundInEveryBranchIsNoLongerConstrainedAfterThem' => [
                'code' => '<?php
                    final class A {
                        /** @var array<string, mixed> */
                        public array $data = [];

                        public function set(bool $b, string $value): void {
                            $ref = &$this->data;
                            if ($b) {
                                $ref = &$ref["a"];
                            } elseif (rand(0, 1)) {
                                $ref = &$ref["b"];
                            } else {
                                $ref = &$ref["c"];
                            }
                            $ref = $value;
                        }
                    }',
            ],
            'referenceToPropertyReboundInEverySwitchCaseIsNoLongerConstrainedAfterIt' => [
                'code' => '<?php
                    final class A {
                        /** @var array<string, mixed> */
                        public array $data = [];

                        public function set(int $k, string $value): void {
                            $ref = &$this->data;
                            switch ($k) {
                                case 1:
                                    $ref = &$ref["a"];
                                    break;
                                default:
                                    $ref = &$ref["b"];
                            }
                            $ref = $value;
                        }
                    }',
            ],
            'referenceToPropertyReboundInATernaryIsNoLongerConstrainedAfterIt' => [
                'code' => '<?php
                    final class A {
                        /** @var array<string, mixed> */
                        public array $data = [];

                        public function set(bool $b, string $value): void {
                            $ref = &$this->data;
                            $b ? ($ref = &$ref["a"]) : ($ref = &$ref["b"]);
                            $ref = $value;
                        }
                    }',
            ],
            'referenceToPropertyReboundToAnotherPropertyInABranch' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                        public string $s = "";
                    }

                    function f(A $a, bool $b): void {
                        $x = &$a->i;
                        if ($b) {
                            $x = &$a->s;
                            $x = "s";
                        }
                        $x = 1;
                    }',
            ],
            'referenceToTemplatedPropertyOfObjectWithoutTypeArguments' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    function f(Box $box): void {
                        $x = &$box->value;
                        $x = 2;
                    }',
            ],
            'referenceToItemOfTypedArrayPropertyHasTheItemType' => [
                'code' => '<?php
                    final class A {
                        /** @var array<string, list<int>> */
                        public array $map = [];
                    }

                    $a = new A();
                    $key = str_repeat("a", rand(1, 2));
                    $x = &$a->map[$key];',
                'assertions' => [
                    '$x===' => 'list<int>',
                ],
                'ignored_issues' => ['PossiblyInvalidPropertyAssignmentValue'],
            ],
            'referenceToItemOfMixedArrayPropertyCheckedAgainstNull' => [
                'code' => '<?php
                    final class Cache {
                        private static array $cache = [];

                        public static function get(string $key): mixed {
                            if (null !== $value = &self::$cache[$key]) {
                                return $value[0];
                            }
                            $value = [1];
                            return null;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess'],
                'php_version' => '8.0',
            ],
            'referenceToPropertyReboundIsNoLongerConstrained' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                    }

                    function rebind(A $a): void {
                        $x = &$a->i;
                        $x = 1;
                        $local = "";
                        $x = &$local;
                        $x = "a";
                    }

                    function unsetReference(A $a): void {
                        $x = &$a->i;
                        $x = 1;
                        unset($x);
                        $x = "a";
                        echo $x;
                    }',
            ],
            'variablePassedByReferenceReboundIsNoLongerConstrained' => [
                'code' => '<?php
                    function setInt(int &$i): void {
                        $i = 1;
                    }

                    function rebind(): void {
                        $x = 0;
                        setInt($x);
                        $local = "";
                        $x = &$local;
                        $x = "a";
                        echo $x;
                    }',
            ],
            'staticVariableKeepsItsConstraint' => [
                'code' => '<?php
                    function counter(): int {
                        /** @var int */
                        static $count = 0;
                        $count++;
                        return $count;
                    }',
            ],
            'referenceToTemplatedPropertyAcceptsTheTypeOfTheObject' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /** @param Box<int> $box */
                    function f(Box $box): void {
                        $x = &$box->value;
                        $x = 2;
                    }',
            ],
            'referenceToStaticPropertyHoldsItsDeclaredTypeAfterEachCallWritingIt' => [
                'code' => '<?php
                    final class A {
                        public static ?int $n = null;

                        public static function reset(): void {
                            self::$n = null;
                        }
                    }

                    A::$n = 5;
                    $n = &A::$n;
                    A::reset();
                    $n = 1;
                    A::reset();',
                'assertions' => [
                    '$n' => 'int|null',
                ],
            ],
            'referenceToPropertyHoldsItsDeclaredTypeAfterACallWritesIt' => [
                'code' => '<?php
                    final class A {
                        public ?int $n = null;
                        public static ?int $s = null;

                        public function resetN(): void {
                            $this->n = null;
                        }

                        public static function resetS(): void {
                            self::$s = null;
                        }
                    }

                    $a = new A();
                    $a->n = 5;
                    $n = &$a->n;
                    $a->resetN();

                    A::$s = 5;
                    $s = &A::$s;
                    A::resetS();',
                'assertions' => [
                    '$n' => 'int|null',
                    '$s' => 'int|null',
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
            'foreachByReferenceOverAPropertyChecksItsItemType' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public array $ints = [];

                        public function f(): void {
                            foreach ($this->ints as &$v) {
                                $v = "s";
                            }
                        }
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'foreachByReferenceOverAnItemOfAStaticPropertyChecksItsItemType' => [
                'code' => '<?php
                    final class A {
                        /** @var array<string, list<int>> */
                        public static array $nested = ["k" => []];

                        public static function f(): void {
                            foreach (self::$nested["k"] as &$v) {
                                $v = "s";
                            }
                        }
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'paramOutOfAByRefPropertyBeforeTheArgumentThatBindsItsTemplateIsCheckedAgainstItsDeclaredType' => [
                'code' => '<?php
                    /**
                     * @template TFlags as int
                     * @param TFlags $flags
                     * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                     */
                    function before2(mixed &$matches = [], int $flags = 0): void {}

                    final class D {
                        public int $i = 0;

                        public function go(): void {
                            before2($this->i, 256);
                        }
                    }',
                'error_message' => "cannot be assigned type ''M256",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'paramOutOnMethodWithBodyThatDoesNotMatchIt' => [
                'code' => '<?php
                    class B {
                        /** @param-out int $a */
                        public function a(?int &$a = null): void {}
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'functionParameterViolation' => [
                'code' => '<?php
                    /** @return void */
                    function changeInt(int &$a) {
                      $a = "hello";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'classMethodParameterViolation' => [
                'code' => '<?php
                    class A {
                      /** @var int */
                      private $foo;

                        public function __construct(int &$foo) {
                            $this->foo = &$foo;
                            $foo = "hello";
                        }
                    }

                    $bar = 5;
                    $a = new A($bar); // $bar is constrained to an int
                    $bar = null; // ReferenceConstraintViolation issue emitted',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'classMethodParameterViolationInPostAssignment' => [
                'code' => '<?php
                    class A {
                      /** @var int */
                      private $foo;

                        public function __construct(int &$foo) {
                            $this->foo = &$foo;
                        }
                    }

                    $bar = 5;
                    $a = new A($bar);
                    $bar = null;',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'contradictoryReferenceConstraints' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        private $foo;

                        public function __construct(int &$foo) {
                            $this->foo = &$foo;
                        }
                    }

                    class B {
                        /** @var string */
                        private $bar;

                        public function __construct(string &$bar) {
                            $this->bar = &$bar;
                        }
                    }

                    if (rand(0, 1)) {
                        $v = 5;
                        $c = (new A($v)); // $v is constrained to an int
                    } else {
                        $v = "hello";
                        $c =  (new B($v)); // $v is constrained to a string
                    }

                    $v = 8;',
                'error_message' => 'ConflictingReferenceConstraint',
            ],
            'invalidDocblockForBadAnnotation' => [
                'code' => '<?php
                    /**
                     * @param-out array<a(),bool> $ar
                     */
                    function foo(array &$ar) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'preventTernaryPassedByReference' => [
                'code' => '<?php
                    /**
                     * @param string $p
                     */
                    function b(&$p): string {
                        return $p;
                    }

                    function main(bool $a, string $b, string $c): void {
                        b($a ? $b : $c);
                    }',
                'error_message' => 'InvalidPassByReference',
            ],
            'referenceToTypedArrayConstrainsAssignment' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var list<int> */
                        public array $arr = [];

                        public function __construct()
                        {
                            assert(isset($this->arr[0]));
                            $int = &$this->arr[0];
                            $int = (string) $int;
                        }
                    }
                ',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'referenceToTypedArrayConstrainsAssignmentWithNullReferenceInitialization' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var list<int> */
                        public array $arr = [];

                        public function __construct()
                        {
                            $int = &$this->arr[0]; // If $this->arr[0] isn\'t set, this will set it to null.
                        }
                    }
                ',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'referenceToPropertyConstrainsAssignment' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                    }

                    function f(A $a): void {
                        $x = &$a->i;
                        $x = "";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'referenceToStaticPropertyConstrainsAssignment' => [
                'code' => '<?php
                    final class A {
                        public static int $i = 0;

                        public static function f(): void {
                            $x = &static::$i;
                            $x = "";
                        }
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'referenceBoundToAReferenceToPropertyConstrainsAssignment' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                    }

                    function f(A $a): void {
                        $x = &$a->i;
                        $y = &$x;
                        $y = "";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'referenceToPropertyKeepsLiteralsOfItsDeclaredType' => [
                'code' => '<?php
                    final class A {
                        /** @var 1|2 */
                        public int $i = 1;
                    }

                    function f(A $a): void {
                        $x = &$a->i;
                        $x = 3;
                    }',
                'error_message' => 'ReferenceConstraintViolation - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:25 - Variable $x is limited to values of type 1|2',
            ],
            'referenceToTemplatedPropertyConstrainsAssignment' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /** @param Box<int> $box */
                    function f(Box $box): void {
                        $x = &$box->value;
                        $x = "";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'referenceToPrivateSetPropertyNeedsWriteAccess' => [
                'code' => '<?php
                    final class A {
                        public private(set) int $i = 0;
                    }

                    function f(A $a): void {
                        $x = &$a->i;
                        echo $x;
                    }',
                'error_message' => 'InaccessibleProperty',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'referenceToPropertyPossiblyReboundToAnotherPropertyIsLimitedToBoth' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                        public string $s = "";
                    }

                    function f(A $a, bool $b): void {
                        $x = &$a->i;
                        if ($b) {
                            $x = &$a->s;
                        }
                        $x = 1.5;
                    }',
                'error_message' => 'ReferenceConstraintViolation - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:25 - Variable $x is limited to values of type int|string because it is a reference to $a->i or $a->s',
            ],
            'constraintFromALaterBranchAfterAReferenceWasRebound' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                    }

                    function takesInt(int &$i): void {}

                    function f(A $a, bool $b, bool $c): void {
                        $x = &$a->i;
                        $s = 0;
                        if ($b) {
                            $x = &$s;
                        }
                        if ($c) {
                            takesInt($x);
                        }
                        $x = [];
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
            'referenceToPropertyReboundToTheSamePropertyInNestedBranchesNamesItOnce' => [
                'code' => '<?php
                    final class A {
                        public int $i = 0;
                        public string $s = "";
                    }

                    function f(A $a, bool $b, bool $c): void {
                        $x = &$a->i;
                        if ($b) {
                            if ($c) {
                                $x = &$a->i;
                            } else {
                                $x = &$a->s;
                            }
                        }
                        $x = 1.5;
                    }',
                'error_message' => 'because it is a reference to $a->i or $a->s, float',
            ],
            'arrayItemBoundToPropertyStaysUnsupported' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public array $ints = [];
                    }

                    /** @param array<array-key, mixed> $arr */
                    function f(array $arr, A $a): void {
                        $arr["k"] = &$a->ints;
                        $arr["k"] = "s";
                    }',
                'error_message' => 'UnsupportedPropertyReferenceUsage',
            ],
            'referenceToShapeItemConstrainsAssignment' => [
                'code' => '<?php
                    final class A {
                        /** @var array{a: int, b: string} */
                        public array $shape = ["a" => 1, "b" => ""];
                    }

                    function f(A $a): void {
                        $x = &$a->shape["a"];
                        $x = "";
                    }',
                'error_message' => 'ReferenceConstraintViolation',
            ],
        ];
    }

    public function testAByRefParamWhoseOutTypeReadsALaterParamIsCheckedOnce(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.0', 'tests');
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @template TFlags as int
                 * @param TFlags $flags
                 * @param-out (TFlags is 256 ? "M256" : "M0") $matches
                 */
                function before2(mixed &$matches = [], int $flags = 0): void {}

                final class D {
                    public int $i = 0;

                    public function go(): void {
                        before2($this->i, 256);
                    }
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $types = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            $types[] = $issue->type;
        }

        $this->assertSame(['InvalidPropertyAssignmentValue'], $types);
    }
}
