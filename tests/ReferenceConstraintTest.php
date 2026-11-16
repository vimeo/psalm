<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
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
}
