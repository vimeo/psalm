<?php

namespace Psalm\Tests;

use DateTime;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class PropertyTypeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testForgetPropertyAssignments(): void
    {
        $this->expectExceptionMessage('NullableReturnStatement');
        $this->expectException(CodeException::class);
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;

                    public function getX(): int {
                        $this->x = 5;

                        XCollector::modify();

                        return $this->x;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsPassesNormally(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;

                    public function getX(): int {
                        $this->x = 5;

                        XCollector::modify();

                        return $this->x;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsInBranch(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;
                }

                function testX(X $x): void {
                    $x->x = 5;

                    if (rand(0, 1)) {
                        XCollector::modify();
                    }

                    if ($x->x === null) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testFooBar(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                    class A {
                        private ?int $bar = null;

                        public function baz(): void
                        {
                            $this->bar = null;

                            foreach (range(1, 5) as $part) {
                                if ($part === 3) {
                                    $this->foo();
                                }
                            }

                            if ($this->bar === null) {}
                        }

                        private function foo() : void {
                            $this->bar = 5;
                        }
                    }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetFinalMethodCalls(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;

                    public function __construct(?int $x) {
                        $this->x = $x;
                    }

                    public final function getX() : ?int {
                        return $this->x;
                    }
                }

                function testX(X $x): void {
                    if (is_int($x->getX())) {
                        XCollector::modify();
                        if ($x->getX() === null) {}
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testRememberImmutableMethodCalls(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->expectExceptionMessage('TypeDoesNotContainNull - somefile.php:22:29');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    public static function modify() : void {}
                }

                /** @psalm-immutable */
                class X {
                    public ?int $x = null;

                    public function __construct(?int $x) {
                        $this->x = $x;
                    }

                    public function getX() : ?int {
                        return $this->x;
                    }
                }

                function testX(X $x): void {
                    if ($x->getX() !== null) {
                        XCollector::modify();
                        if ($x->getX() === null) {}
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testRememberImmutableProperties(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->expectExceptionMessage('TypeDoesNotContainNull - somefile.php:18:29');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    public static function modify() : void {}
                }

                /** @psalm-immutable */
                class X {
                    public ?int $x = null;

                    public function __construct(?int $x) {
                        $this->x = $x;
                    }
                }

                function testX(X $x): void {
                    if ($x->x !== null) {
                        XCollector::modify();
                        if ($x->x === null) {}
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoCrashInTryCatch(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                function maybeMutates() : void {}

                class X {
                    public int $f = 0;

                    public function validate(): void {
                        try {
                        } finally {
                            $this->f = 1;
                            maybeMutates();
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileOne(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function one(): bool {
                        $has_changes = false;

                        while ($this->a) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileTwo(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];

                    public function two(): bool {
                        $has_changes = false;

                        while ($this->a || $this->b) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileThree(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function three(): bool {
                        $has_changes = false;

                        while ($this->a || $this->b || $this->c) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileFour(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function four(): bool {
                        $has_changes = false;

                        while (($this->a && $this->b) || $this->c) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileFive(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function five(): bool {
                        $has_changes = false;

                        while ($this->a || ($this->b && $this->c)) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCrates(): void
    {
        Config::getInstance()->addUniversalObjectCrate(DateTime::class);

        $this->addFile(
            'somefile.php',
            '<?php
                $f = new \DateTime();
                // reads are fine
                $f->bar;

                // sets are fine
                $f->buzz = false;
        ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsInBranchWithThrowNormally(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    /** @var ?int **/
                    public $x;

                    public function getX(bool $b): int {
                        $this->x = 5;

                        if ($b) {
                            XCollector::modify();
                            throw new \Exception("bad");
                        }

                        return $this->x;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'newVarInIf' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        public $foo = "hello";

                        /** @return void */
                        public function barBar()
                        {
                            if (rand(0,10) === 5) {
                                $this->foo = [];
                            }

                            if (!is_array($this->foo)) {
                                // do something
                            }
                        }
                    }',
            ],
            'propertyWithoutTypeSuppressingIssue' => [
                'code' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    $a = (new A)->foo;',
                'assertions' => [],
                'ignored_issues' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'promotedPropertyNoExtendedConstructor' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(
                            public string $name,
                        ) {}
                    }

                    class B extends A
                    {
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyWithoutTypeSuppressingIssueAndAssertingNull' => [
                'code' => '<?php
                    class A {
                        /** @return void */
                        function foo() {
                            $boop = $this->foo === null && rand(0,1);

                            echo $this->foo->baz;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedThisPropertyFetch',
                    'MixedAssignment',
                    'MixedArgument',
                    'MixedMethodCall',
                    'MixedPropertyFetch',
                ],
            ],
            'sharedPropertyInIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $foo = 0;
                    }
                    class B {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new A(): (rand(0, 10) ? new B() : null);
                    $b = null;

                    if ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'int|null|string',
                ],
            ],
            'sharedPropertyInElseIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $foo = 0;
                    }
                    class B {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new A() : new B();
                    if (rand(0, 1)) {
                        $a = null;
                    }
                    $b = null;

                    if (rand(0, 10) === 4) {
                        // do nothing
                    }
                    elseif ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'int|null|string',
                ],
            ],
            'nullablePropertyCheck' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $aa = "";
                    }

                    class B {
                        /** @var A|null */
                        public $bb;
                    }

                    $b = rand(0, 10) ? new A() : new B();

                    if ($b instanceof B && isset($b->bb) && $b->bb->aa === "aa") {
                        echo $b->bb->aa;
                    }',
            ],
            'nullablePropertyAfterGuard' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        public $aa;
                    }

                    $a = new A();

                    if (!$a->aa) {
                        $a->aa = "hello";
                    }

                    echo substr($a->aa, 1);',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'nullableStaticPropertyWithIfCheck' => [
                'code' => '<?php
                    class A {
                        /** @var A|null */
                        public static $fooFoo;

                        public static function getFoo(): A {
                            if (!self::$fooFoo) {
                                self::$fooFoo = new A();
                            }

                            return self::$fooFoo;
                        }
                    }',
            ],
            'reflectionProperties' => [
                'code' => '<?php
                    class Foo {
                    }

                    $a = new \ReflectionMethod(Foo::class, "__construct");

                    echo $a->name . " - " . $a->class;',
            ],
            'grandparentReflectedProperties' => [
                'code' => '<?php
                    $a = new DOMElement("foo");
                    $owner = $a->ownerDocument;',
                'assertions' => [
                    '$owner' => 'DOMDocument',
                ],
            ],
            'propertyMapHydration' => [
                'code' => '<?php
                    function foo(DOMElement $e) : void {
                        echo $e->attributes->length;
                    }',
            ],
            'genericTypeFromPropertyMap' => [
                'code' => '<?php
                    function foo(DOMElement $e) : ?DOMAttr {
                        return $e->attributes->item(0);
                    }',
            ],
            'goodArrayProperties' => [
                'code' => '<?php
                    interface I1 {}

                    class A1 implements I1{}

                    class B1 implements I1 {}

                    class C1 {
                        /** @var array<I1> */
                        public $is = [];
                    }

                    $c = new C1;
                    $c->is = [new A1];
                    $c->is = [new A1, new A1];
                    $c->is = [new A1, new B1];',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'issetPropertyDoesNotExist' => [
                'code' => '<?php
                    class A {
                    }

                    $a = new A();

                    if (isset($a->bar)) {

                    }',
            ],
            'notSetInConstructorButHasDefault' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 0;

                        public function __construct() { }
                    }',
            ],
            'propertySetInPrivateMethod' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        private function foo(): void {
                            $this->a = 5;
                        }
                    }',
            ],
            'definedInTraitSetInConstructor' => [
                'code' => '<?php
                    trait A {
                        /** @var string **/
                        public $a;
                    }
                    class B {
                        use A;

                        public function __construct() {
                            $this->a = "hello";
                        }
                    }',
            ],
            'propertySetInNestedPrivateMethod' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        private function foo(): void {
                            $this->bar();
                        }

                        private function bar(): void {
                            $this->a = 5;
                        }
                    }',
            ],
            'propertyArrayIssetAssertion' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public $a = [];

                        private function foo(): void {
                            if (isset($this->a["hello"])) {
                                bar($this->a["hello"]);
                            }
                        }
                    }',
            ],
            'propertyArrayIssetAssertionWithVariableOffset' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public $a = [];

                        private function foo(): void {
                            $b = "hello";

                            if (!isset($this->a[$b])) {
                                return;
                            }

                            bar($this->a[$b]);
                        }
                    }',
            ],
            'staticPropertyArrayIssetAssertionWithVariableOffset' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public static $a = [];
                    }

                    function foo(): void {
                        $b = "hello";

                        if (!isset(A::$a[$b])) {
                            return;
                        }

                        bar(A::$a[$b]);
                    }',
            ],
            'staticPropertyArrayIssetAssertionWithVariableOffsetAndElse' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public static $a = [];
                    }

                    function foo(): void {
                        $b = "hello";

                        if (!isset(A::$a[$b])) {
                            $g = "bar";
                        } else {
                            bar(A::$a[$b]);
                            $g = "foo";
                        }

                        bar($g);
                    }',
            ],
            'traitConstructor' => [
                'code' => '<?php
                    trait T {
                      /** @var string **/
                      public $foo;

                      public function __construct() {
                        $this->foo = "hello";
                      }
                    }

                    class A {
                        use T;
                    }',
            ],
            'abstractClassWithNoConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }',
            ],
            'abstractClassConstructorAndChildConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            $this->foo = "";
                        }
                    }

                    class B extends A {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'abstractClassConstructorAndImplicitChildConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        public function __construct(int $bar) {
                            $this->foo = (string)$bar;
                        }
                    }

                    class B extends A {}

                    class E extends \Exception{}',
            ],
            'notSetInEmptyConstructor' => [
                'code' => '<?php
                    /** @psalm-suppress PropertyNotSetInConstructor */
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() { }
                    }',
            ],
            'extendsClassWithPrivateConstructorSet' => [
                'code' => '<?php
                    namespace Q;

                    class Base
                    {
                        /**
                         * @var string
                         */
                        private $aString;

                        public function __construct()
                        {
                            $this->aString = "aa";
                            echo($this->aString);
                        }
                    }

                    class Descendant extends Base
                    {
                        /**
                         * @var bool
                         */
                        private $aBool;

                        public function __construct()
                        {
                            parent::__construct();
                            $this->aBool = true;
                        }
                    }',
            ],
            'extendsClassWithPrivateAndException' => [
                'code' => '<?php
                    abstract class A extends \Exception {
                        /** @var string **/
                        private $p;

                        /** @param string $p **/
                        final public function __construct($p) {
                            $this->p = $p;
                        }
                    }

                    final class B extends A {}',
            ],
            'setInAbstractMethod' => [
                'code' => '<?php
                    interface I {
                        public function foo(): void;
                    }

                    /** @psalm-suppress PropertyNotSetInConstructor */
                    abstract class A implements I {
                        /** @var string */
                        public $bar;

                        public function __construct() {
                            $this->foo();
                        }
                    }

                    class B extends A {
                        public function foo(): void {
                            $this->bar = "hello";
                        }
                    }',
            ],
            'callsPrivateParentMethodThenUsesParentInitializedProperty' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $bar;

                        public function __construct() {
                            $this->setBar();
                        }

                        private function setBar(): void {
                            $this->bar = "hello";
                        }
                    }

                    class B extends A {
                        public function __construct() {
                            parent::__construct();

                            echo $this->bar;
                        }
                    }',
            ],
            'setInFinalMethod' => [
                'code' => '<?php
                    class C
                    {
                        /**
                         * @var string
                         */
                        private $a;

                        /**
                         * @var string
                         */
                        private $b;

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        public function __construct(array $opts)
                        {
                            $this->setOptions($opts);
                        }

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        final public function setOptions(array $opts): void
                        {
                            $this->a = $opts["a"] ?? "defaultA";
                            $this->b = $opts["b"] ?? "defaultB";
                        }
                    }',
            ],
            'setInFinalClass' => [
                'code' => '<?php
                    final class C
                    {
                        /**
                         * @var string
                         */
                        private $a;

                        /**
                         * @var string
                         */
                        private $b;

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        public function __construct(array $opts)
                        {
                            $this->setOptions($opts);
                        }

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        public function setOptions(array $opts): void
                        {
                            $this->a = $opts["a"] ?? "defaultA";
                            $this->b = $opts["b"] ?? "defaultB";
                        }
                    }',
            ],
            'selfPropertyType' => [
                'code' => '<?php
                    class Node
                    {
                        /** @var self|null */
                        public $next;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->next = new Node();
                            }
                        }
                    }

                    $node = new Node();
                    $next = $node->next;',
                'assertions' => [
                    '$next' => 'Node|null',
                ],
            ],
            'perPropertySuppress' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var int
                         * @psalm-suppress PropertyNotSetInConstructor
                         */
                        public $a;

                        public function __construct() { }
                    }',
            ],
            'analyzePropertyMappedClass' => [
                'code' => '<?php
                    namespace PhpParser\Node\Stmt;

                    use PhpParser\Node;

                    class Finally_ extends Node\Stmt
                    {
                        /** @var list<Node\Stmt> Statements */
                        public $stmts;

                        /**
                         * Constructs a finally node.
                         *
                         * @param list<Node\Stmt> $stmts      Statements
                         * @param array<string, mixed>  $attributes Additional attributes
                         */
                        public function __construct(array $stmts = array(), array $attributes = array()) {
                            parent::__construct($attributes);
                            $this->stmts = $stmts;
                        }

                        public function getSubNodeNames() : array {
                            return array("stmts");
                        }

                        public function getType() : string {
                            return "Stmt_Finally";
                        }
                    }',
                'assertions' => [],
            ],
            'privatePropertyAccessible' => [
                'code' => '<?php
                    class A {
                      /** @var string */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = $foo;
                      }

                      private function bar() : void {}
                    }

                    class B extends A {
                      /** @var string */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = $foo;
                        parent::__construct($foo);
                      }
                    }',
            ],
            'privatePropertyAccessibleDifferentType' => [
                'code' => '<?php
                    class A {
                      /** @var int */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = 5;
                      }

                      private function bar() : void {}
                    }

                    class B extends A {
                      /** @var string */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = $foo;
                        parent::__construct($foo);
                      }
                    }',
            ],
            'privatePropertyAccessibleInTwoSubclasses' => [
                'code' => '<?php
                    class A {
                        public function __construct() {}
                    }
                    class B extends A {
                        /**
                         * @var int
                         */
                        private $prop;

                        public function __construct()
                        {
                            parent::__construct();
                            $this->prop = 1;
                        }
                    }
                    class C extends A {
                        /**
                         * @var int
                         */
                        private $prop;

                        public function __construct()
                        {
                            parent::__construct();
                            $this->prop = 2;
                        }
                    }',
            ],
            'noIssueWhenSuppressingMixedAssignmentForProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        public $foo;

                        /** @param mixed $a */
                        public function barBar($a): void
                        {
                            $this->foo = $a;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'propertyAssignmentToMixed' => [
                'code' => '<?php
                    class C {
                        /** @var string|null */
                        public $foo;
                    }

                    /** @param mixed $a */
                    function barBar(C $c, $a): void
                    {
                        $c->foo = $a;
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'propertySetInBothIfBranches' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int */
                        private $status;

                        public function __construct(int $in)
                        {
                            if (rand(0, 1)) {
                                $this->status = 1;
                            } else {
                                $this->status = $in;
                            }
                        }
                    }',
            ],
            'propertySetInPrivateMethodWithIfAndElse' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->foo();
                            } else {
                                $this->bar();
                            }
                        }

                        private function foo(): void {
                            $this->a = 5;
                        }

                        private function bar(): void {
                            $this->a = 5;
                        }
                    }',
            ],
            'allowMixedAssignmentWhenDesired' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        private $mixed = "hello";

                        /**
                         * @param mixed $value
                         */
                        public function setMixed($value): void
                        {
                            $this->mixed = $value;
                        }
                    }',
            ],
            'suppressUndefinedThisPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            /** @psalm-suppress UndefinedThisPropertyAssignment */
                            $this->bar = rand(0, 1) ? "hello" : null;
                        }

                        /** @psalm-suppress UndefinedThisPropertyFetch */
                        public function foo() : void {
                            if ($this->bar === null && rand(0, 1)) {}
                        }
                    }',
            ],
            'suppressUndefinedPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            /** @psalm-suppress UndefinedThisPropertyAssignment */
                            $this->bar = rand(0, 1) ? "hello" : null;
                        }
                    }

                    $a = new A();
                    /** @psalm-suppress UndefinedPropertyFetch */
                    if ($a->bar === null && rand(0, 1)) {}',
            ],
            'setPropertiesOfStdClass' => [
                'code' => '<?php
                    $a = new stdClass();
                    $a->b = "c";',
                'assertions' => [
                    '$a' => 'stdClass',
                    '$a->b' => 'string',
                ],
            ],
            'getPropertiesOfSimpleXmlElement' => [
                'code' => '<?php
                    $a = new SimpleXMLElement("<person><child role=\"son\"></child></person>");
                    $b = $a->b;',
                'assertions' => [
                    '$a' => 'SimpleXMLElement',
                    '$a->b' => 'SimpleXMLElement|null',
                    '$b' => 'SimpleXMLElement|null',
                ],
            ],
            'allowLessSpecificReturnTypeForOverriddenMethod' => [
                'code' => '<?php
                    class A {
                        public function aa(): ?string {
                            return "bar";
                        }
                    }

                    class B extends A {
                        public static function aa(): ?string {
                            return rand(0, 1) ? "bar" : null;
                        }
                    }

                    class C extends A {
                        public static function aa(): ?string {
                            return "bar";
                        }
                    }',
            ],
            'allowLessSpecificReturnTypeForInterfaceMethod' => [
                'code' => '<?php
                    interface Foo {
                        public static function foo(): ?string;
                    }

                    class Bar implements Foo {
                        public static function foo(): ?string
                        {
                            return "bar";
                        }
                    }

                    class Baz implements Foo {
                        /**
                         * @return string $baz
                         */
                        public static function foo(): ?string
                        {
                            return "baz";
                        }
                    }

                    class Bax implements Foo {
                        /**
                         * @return null|string $baz
                         */
                        public static function foo(): ?string
                        {
                            return "bax";
                        }
                    }

                    class Baw implements Foo {
                        /**
                         * @return null|string $baz
                         */
                        public static function foo(): ?string
                        {
                            /** @var null|string $val */
                            $val = "baw";

                            return $val;
                        }
                    }',
            ],
            'staticPropertyMethodCall' => [
                'code' => '<?php
                    class A {
                        /** @var self|null */
                        public static $instance;

                        /** @var string|null */
                        public $bat;

                        public function foo() : void {
                            if (self::$instance) {
                                self::$instance->bar();
                                echo self::$instance->bat;
                            }
                        }

                        public function bar() : void {}
                    }

                    if (A::$instance) {
                        A::$instance->bar();
                        echo A::$instance->bat;
                    }',
            ],
            'nonStaticPropertyMethodCall' => [
                'code' => '<?php
                    class A {
                        /** @var self|null */
                        public $instance;

                        /** @var string|null */
                        public $bat;

                        public function foo() : void {
                            if ($this->instance) {
                                $this->instance->bar();
                                echo $this->instance->bat;
                            }
                        }

                        public function bar() : void {}
                    }

                    $a = new A();

                    if ($a->instance) {
                        $a->instance->bar();
                        echo $a->instance->bat;
                    }',
            ],
            'staticPropertyOfStaticTypeMethodCall' => [
                'code' => '<?php
                    class A {
                        /** @var static|null */
                        public $instance;
                    }

                    class B extends A {
                        /** @var string|null */
                        public $bat;

                        public function foo() : void {
                            if ($this->instance) {
                                $this->instance->bar();
                                echo $this->instance->bat;
                            }
                        }

                        public function bar() : void {}
                    }',
            ],
            'classStringPropertyType' => [
                'code' => '<?php
                    class C {
                        /** @psalm-var array<class-string, int> */
                        public $member = [
                            InvalidArgumentException::class => 1,
                        ];
                    }',
            ],
            'allowPrivatePropertySetAfterInstanceof' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        private $foo;

                        public function bar() : void {
                            if (!$this instanceof B) {
                                return;
                            }

                            $this->foo = "hello";
                        }
                    }

                    class B extends A {}',
            ],
            'noCrashForAbstractConstructorWithInstanceofInterface' => [
                'code' => '<?php
                    abstract class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if ($this instanceof I) {
                                $this->a = $this->bar();
                            } else {
                                $this->a = 6;
                            }
                        }
                    }

                    interface I {
                        public function bar() : int;
                    }',
            ],
            'SKIPPED-abstractConstructorWithInstanceofClass' => [
                'code' => '<?php
                    abstract class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if ($this instanceof B) {
                                $this->a = $this->bar();
                            } else {
                                $this->a = 6;
                            }
                        }
                    }

                    class B extends A {
                        public function bar() : int {
                            return 3;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'inheritDocPropertyTypes' => [
                'code' => '<?php
                    class X {
                        /**
                         * @var string|null
                         */
                        public $a;

                        /**
                         * @var string|null
                         */
                        public static $b;
                    }

                    class Y extends X {
                        public $a = "foo";
                        public static $b = "foo";
                    }

                    (new Y)->a = "hello";
                    echo (new Y)->a;
                    Y::$b = "bar";
                    echo Y::$b;',
            ],
            'subclassPropertySetInParentConstructor' => [
                'code' => '<?php
                    class Base {
                        /** @var string */
                        protected $prop;
                        public function __construct(string $s) {
                            $this->prop = $s;
                        }
                    }

                    class Child extends Base {
                        /** @var string */
                        protected $prop;
                    }',
            ],
            'callInParentContext' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $i = 1;
                    }

                    abstract class B
                    {
                        /**
                         * @var string
                         */
                        protected $foo;

                        /**
                         * @var A[]
                         */
                        private $as = [];

                        public function __construct()
                        {
                            $this->foo = "";
                            $this->bar();
                        }

                        public function bar(): void
                        {
                            \usort($this->as, function (A $a, A $b): int {
                                return $b->i <=> $a->i;
                            });
                        }
                    }

                    class C extends B
                    {
                        public function __construct()
                        {
                            parent::__construct();
                        }
                    }',
            ],
            'staticVarSelf' => [
                'code' => '<?php
                    class Foo {
                        /** @var self */
                        public static $current;
                    }

                    $a = Foo::$current;',
                'assertions' => [
                    '$a' => 'Foo',
                ],
            ],
            'noMixedErrorWhenAssignmentExpectsMixed' => [
                'code' => '<?php
                    class A {
                        /** @var array<string, mixed> $bar */
                        public $bar = [];

                        /** @param mixed $b */
                        public function foo($b) : void {
                            $this->bar["a"] = $b;
                        }
                    }',
            ],
            'propertySetInGrandparentExplicitly' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         */
                        public $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }
                    }
                    class B extends A {}
                    class C extends B {
                        public function __construct(string $s) {
                            A::__construct($s);
                        }
                    }',
            ],
            'propertySetInGrandparentImplicitly' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         */
                        public $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }
                    }
                    class B extends A {}
                    class C extends B {}',
            ],
            'uninitializedPropertySuppressPropertyNotSetInConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            $this->setFoo(); // public method that circumvents checks
                            echo strlen($this->foo);
                        }

                        public function setFoo() : void {
                            $this->foo = "foo";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor'],
            ],
            'uninitializedPropertySuppressPropertyNotSetInAbstractConstructor' => [
                'code' => '<?php
                    abstract class A {
                          /** @readonly */
                          public string $s;

                          abstract public function __construct(string $s);
                    }',
            ],
            'setTKeyedArrayPropertyType' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-var array{from:bool, to:bool}
                         */
                        protected $changed = [
                            "from" => false,
                            "to" => false,
                        ];

                        /**
                         * @psalm-param "from"|"to" $property
                         */
                        public function ChangeThing(string $property) : void {
                            $this->changed[$property] = true;
                        }
                    }',
            ],
            'noRedundantConditionWhenCheckingInitializations' => [
                'code' => '<?php
                    final class Clazz {
                        /**
                         * @var bool
                         */
                        public $x;

                        /**
                         * @var int
                         */
                        public $y = 0;

                        public function func1 (): bool {
                            if ($this->y) {
                                return true;
                            }
                            return false;
                        }

                        public function func2 (): int {
                            if ($this->y) {
                                return 1;
                            }
                            return 2;
                        }

                        public function __construct () {
                            $this->x = false;
                            if ($this->func1()) {
                                $this->y = $this->func2();
                            }
                            $this->func2();
                        }
                    }',
            ],
            'noRedundantConditionWhenCheckingInitializationsEdgeCases' => [
                'code' => '<?php
                    final class Clazz {
                        /**
                         * @var bool
                         */
                        public $x;

                        /**
                         * @var int
                         */
                        public $y = 0;

                        public function func1 (): bool {
                            if ($this->y !== 0) {
                                return true;
                            }
                            return false;
                        }

                        public function func2 (): int {
                            if ($this->y !== 0) {
                                return $this->y;
                            }
                            return 2;
                        }

                        public function __construct () {
                            $this->x = false;
                            if ($this->func1()) {
                                $this->y = $this->func2();
                            }
                            $this->func2();
                        }
                    }',
            ],
            'propertySetInProtectedMethodWithConstant' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        protected function foo(): void {
                            $this->a = 5;
                        }
                    }

                    class B extends A {
                        const HELLO = "HELLO";

                        protected function foo() : void {
                            $this->a = 6;

                            echo self::HELLO;
                        }
                    }',
            ],
            'setPropertyInParentProtectedMethodExplicitCall' => [
                'code' => '<?php
                    abstract class A {
                        public function __construct() {
                            $this->overriddenByB();
                        }

                        protected function overriddenByB(): void {
                            // do nothing
                        }
                    }

                    class B extends A {
                        /** @var int */
                        private $foo;

                        /** @var int */
                        protected $bar;

                        public function __construct() {
                            parent::__construct();
                        }

                        protected final function overriddenByB(): void {
                            $this->foo = 1;
                            $this->bar = 1;
                        }
                    }',
            ],
            'setPropertyInParentProtectedMethodImplicitCall' => [
                'code' => '<?php
                    abstract class A {
                        public function __construct() {
                            $this->overriddenByB();
                        }

                        protected function overriddenByB(): void {
                            // do nothing
                        }
                    }

                    class B extends A {
                        /** @var int */
                        private $foo;

                        /** @var int */
                        protected $bar;

                        protected final function overriddenByB(): void {
                            $this->foo = 1;
                            $this->bar = 1;
                        }
                    }',
            ],
            'setPropertyInParentWithPrivateConstructor' => [
                'code' => '<?php
                    namespace NS;

                    class Base
                    {
                        /**
                         * @var int
                         */
                        protected $a;

                        final private function __construct()
                        {
                            $this->setA();
                        }

                        private function setA() : void {
                            $this->a = 5;
                        }

                        public static function getInstance(): self { return new static; }
                    }

                    class Concrete extends Base {}',
            ],
            'preventCrashWhenCallingInternalMethodInPropertyInitialisationChecks' => [
                'code' => '<?php
                    class Foo extends \RuntimeException {
                        /** @var array */
                        protected $serializableTrace;

                        public function __construct() {
                            parent::__construct("hello", 0);
                            $this->serializableTrace = $this->getTrace();
                        }
                    }

                    class Bar extends Foo {}',
            ],
            'inferPropertyTypesForSimpleConstructors' => [
                'code' => '<?php
                    class A {
                        private $foo;
                        private $bar;

                        public function __construct(int $foot, string $bart) {
                            $this->foo = $foot;
                            $this->bar = $bart;
                        }

                        public function getFoo() : int {
                            return $this->foo;
                        }

                        public function getBar() : string {
                            return $this->bar;
                        }
                    }',
            ],
            'nullableDocblockTypedPropertyNoConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;
                    }',
            ],
            'nullableDocblockTypedPropertyEmptyConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;

                        public function __construct() {}
                    }',
            ],
            'nullableDocblockTypedPropertyUseBeforeInitialised' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
            ],
            'dontAlterClosureParams' => [
                'code' => '<?php
                    class C {
                      /** @var array */
                      public $i;

                      public function __construct() {
                        $this->i = [
                          function (Exception $e): void {},
                          function (LogicException $e): void {},
                        ];
                      }
                    }',
            ],
            'inferSpreadParamType' => [
                'code' => '<?php
                    class Tag {}
                    class EntityTags {
                        private $tags;

                        /** @no-named-arguments */
                        public function __construct(Tag ...$tags) {
                            $this->tags = $tags;
                        }
                    }',
            ],
            'staticPropertyDefaultWithStaticType' => [
                'code' => '<?php
                    class Test {
                        /** @var array<int, static> */
                        private static $t1 = [];

                        /** @var array<int, static> */
                        private $t2 = [];
                    }',
            ],
            'propagateIgnoreNullableOnPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public string $s = "hey";
                    }

                    /**
                     * @psalm-ignore-nullable-return
                     */
                    function foo() : ?A {
                        return rand(0, 1) ? new A : null;
                    }

                    function takesString(string $_s) : void {}

                    $foo = foo();

                    if ($foo->s !== null) {}
                    echo $foo->s ?? "bar";
                    takesString($foo->s);',
            ],
            'noMissingPropertyWhenArrayTypeProvided' => [
                'code' => '<?php

                    class Foo {
                        private $bar;

                        /** @psalm-param array{key: string} $bar */
                        public function __construct(array $bar) {
                            $this->bar = $bar;
                        }

                        public function bar(): void {
                            echo $this->bar["key"];
                        }
                    }',
            ],
            'rememberThisPropertyAsssignmentsInMethod' => [
                'code' => '<?php
                    class A {
                        public bool $foo = false;

                        public function bar() : void {
                            $this->foo = false;
                            $this->maybeChange();

                            if ($this->foo) {}
                        }

                        public function maybeChange() : void {
                            if (rand(0, 1)) {
                                $this->foo = true;
                            }
                        }
                    }',
            ],
            'testRemoveClauseAfterReassignment' => [
                'code' => '<?php
                    class Test {
                        /** @var ?bool */
                        private $foo;

                        public function run(): void {
                            $this->foo = false;
                            $this->bar();
                            if ($this->foo === true) {}
                        }

                        private function bar(): void {
                            if (mt_rand(0, 1)) {
                                $this->foo = true;
                            }
                        }
                    }',
            ],
            'allowIssetOnTypedProperty' => [
                'code' => '<?php
                    class A {
                        public string $a;

                        public function __construct(bool $b) {
                            if ($b) {
                                $this->a = "hello";
                            }

                            if (isset($this->a)) {
                                echo $this->a;
                                $this->a = "bello";
                            }

                            $this->a = "bar";
                        }
                    }',
            ],
            'allowGoodArrayPushOnArrayValue' => [
                'code' => '<?php
                    class MyClass {
                        /**
                         * @var int[]
                         */
                        private $prop = [];

                        /**
                         * @return void
                         */
                        public function foo() {
                            array_push($this->prop, 5);
                        }
                    }',
            ],
            'someConditionalCallToParentConstructor' => [
                'code' => '<?php
                    class GrandParentClassDoesNotDefine {
                        public function __construct() {}
                    }

                    class ParentClassDefinesVar extends GrandParentClassDoesNotDefine {
                        protected string $val;

                        public function __construct() {
                            $this->val = "hello";
                            if (rand(0, 1)) {
                                parent::__construct();
                            }
                        }
                    }

                    class ChildClass extends ParentClassDefinesVar {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'noConditionalCallToParentConstructor' => [
                'code' => '<?php
                    class GrandParentClassDoesNotDefine {
                        public function __construct() {}
                    }

                    class ParentClassDefinesVar extends GrandParentClassDoesNotDefine {
                        protected string $val;

                        public function __construct() {
                            $this->val = "hello";
                            parent::__construct();
                        }
                    }

                    class ChildClass extends ParentClassDefinesVar {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'allowByReferenceAssignmentToUninitializedNullableProperty' => [
                'code' => '<?php
                    class C {
                        private ?\Closure $onCancel;

                        public function __construct() {
                            $this->foo($this->onCancel);
                        }

                        /**
                         * @param mixed $onCancel
                         * @param-out \Closure $onCancel
                         */
                        public function foo(&$onCancel) : void {
                            $onCancel = function (): void {};
                        }
                    }',
            ],
            'dontCarryAssertionsOver' => [
                'code' => '<?php
                    class A
                    {
                        private string $network;

                        public function __construct(string $s)
                        {
                            $this->network = $s;
                            $this->firstCheck();
                            $this->secondCheck();
                        }

                        public function firstCheck(): void
                        {
                            if ($this->network === "x") {
                                return;
                            }
                        }

                        public function secondCheck(): void
                        {
                            if ($this->network === "x") {
                                return;
                            }
                        }
                    }',
            ],
            'useVariableAccessInStatic' => [
                'code' => '<?php
                    class A2 {
                        public static string $title = "foo";
                        public static string $label = "bar";
                    }

                    $model = new A2();
                    $message = $model::$title;
                    $message .= $model::$label;
                    echo $message;',
            ],
            'staticPropertyInFinalMethod' => [
                'code' => '<?php
                    abstract class Foo {
                        /** @var static */
                        protected Foo $foo;
                    }

                    final class Bar extends Foo {
                        public function __construct(Bar $bar) {
                            $this->foo = $bar;
                        }

                        public function baz(): Bar {
                            return $this->foo;
                        }
                    }',
            ],
            'aliasedFinalMethod' => [
                'code' => '<?php
                    trait A {
                        private int $prop;
                        public final function setProp(int $prop): void {
                            $this->prop = $prop;
                        }
                    }

                    class B {
                        use A {
                            setProp as setPropFinal;
                        }

                        public function __construct() {
                            $this->setPropFinal(1);
                        }
                    }',
            ],
            'aliasedAsFinalMethod' => [
                'code' => '<?php
                    trait A {
                        private int $prop;
                        public function setProp(int $prop): void {
                            $this->prop = $prop;
                        }
                    }

                    class B {
                        use A {
                            setProp as final setPropFinal;
                        }

                        public function __construct() {
                            $this->setPropFinal(1);
                        }
                    }',
            ],
            'staticPropertyAssertion' => [
                'code' => '<?php
                    class Foo {
                        /** @var int */
                        private static $transactionDepth;

                        function bar(): void {
                            if (self::$transactionDepth === 0) {
                            } else {
                                --self::$transactionDepth;

                                if (self::$transactionDepth === 0) {

                                }
                            }
                        }
                    }',
            ],
            'dontMemoizePropertyTypeAfterRootVarAssertion' => [
                'code' => '<?php
                    class A {
                        public string $i = "";
                    }

                    class B {
                        public int $i = 0;
                    }

                    /** @param A|B $o */
                    function takesAorB(object $o) : void {
                        echo $o->i;
                        if ($o instanceof A) {
                            echo strlen($o->i);
                        }
                    }',
            ],
            'unionPropertyType' => [
                'code' => '<?php
                    class A {
                        public string|int $i;

                        public function __construct() {
                            $this->i = 5;
                            $this->i = "hello";
                        }
                    }

                    $a = new A();

                    if ($a->i === 3) {}
                    if ($a->i === "foo") {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'setClassStringOfStatic' => [
                'code' => '<?php
                    class A {
                        public static array $stack = [];

                        public static function foo() : void {
                            $class = get_called_class();
                            $class::$stack[] = 1;
                        }
                    }',
            ],
            'promotedPublicPropertyWithDefault' => [
                'code' => '<?php
                    class A {
                        public function __construct(public int $foo = 5) {}
                    }

                    echo (new A)->foo;',
            ],
            'promotedPublicPropertyWithoutDefault' => [
                'code' => '<?php
                    class A {
                        public function __construct(public int $foo) {}
                    }

                    echo (new A(5))->foo;',
            ],
            'promotedProtectedProperty' => [
                'code' => '<?php
                    class A {
                        public function __construct(protected int $foo) {}
                    }

                    class AChild extends A {
                        public function bar() : int {
                            return $this->foo;
                        }
                    }',
            ],
            'skipConstructor' => [
                'code' => '<?php
                    class A {
                        protected string $s;

                        public function __construct() {
                            $this->s = "hello";
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        public function __construct()
                        {
                            parent::__construct();

                            echo $this->s;
                        }
                    }',
            ],
            'getPropertyThatMayNotBeSet' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    class A {
                        public string $bar;

                        public function getBar() : string {
                            /** @psalm-suppress RedundantPropertyInitializationCheck */
                            if (!isset($this->bar)) {
                                return "hello";
                            }

                            return $this->bar;
                        }

                        public function getBarAgain() : string {
                            /** @psalm-suppress RedundantPropertyInitializationCheck */
                            if (isset($this->bar)) {
                                return $this->bar;
                            }

                            return "hello";
                        }
                    }',
            ],
            'memoizePropertyAfterSetting' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {
                            /** @psalm-suppress UndefinedThisPropertyAssignment */
                            $this->b = "c";
                            echo strlen($this->b);
                        }
                    }',
            ],
            'noErrorForSplatArgs' => [
                'code' => '<?php
                    class Foo {
                        protected array $b;

                        protected function __construct(?string ...$bb) {
                            $this->b = $bb;
                        }
                    }

                    class Bar extends Foo {}',
            ],
            'noUndefinedPropertyIssueAfterSuppressingOnInterface' => [
                'code' => '<?php
                    interface I {}

                    function bar(I $i) : void {
                        /**
                         * @psalm-suppress NoInterfaceProperties
                         * @psalm-suppress MixedArgument
                         */
                        echo $i->foo;
                    }',
            ],
            'noRedundantCastWhenCheckingProperties' => [
                'code' => '<?php
                    class Foo
                    {
                        public array $map;

                        public function __construct()
                        {
                            $this->map = [];
                            $this->map["test"] = "test";

                            $this->useMap();
                        }

                        public function useMap(): void
                        {
                            $keys = array_keys($this->map);
                            $key = reset($keys);
                            echo (string) $key;
                        }
                    }',
            ],
            'ignoreUndefinedMethodOnUnion' => [
                'code' => '<?php
                    class NullObject {
                        /**
                         * @return null
                         */
                        public function __get(string $s) {
                            return null;
                        }
                    }

                    class User {
                        public string $name = "Dave";
                    }

                    function takesNullableUser(User|NullObject $user) : ?string {
                        $name = $user->name;

                        if ($name === null) {}

                        return $name;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'dynamicPropertyFetch' => [
                'code' => '<?php
                    class Foo {
                        public int $a = 0;
                    }

                    function takesFoo(?Foo $foo, string $b) : void {
                        /** @psalm-suppress MixedArgument */
                        echo $foo->{$b} ?? null;
                    }',
            ],
            'nullCoalesceWithNullablePropertyAccess' => [
                'code' => '<?php
                    class Bar {
                        public ?string $a = null;
                    }

                    function takesBar(?Bar $bar) : string {
                        return $bar?->a ?? "default";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'possiblyNullOnFunctionCallCoalesced' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int */
                        public $a = 0;
                    }

                    function accessOnVar(?Foo $bar, string $b) : void {
                        /** @psalm-suppress MixedArgument */
                        echo $bar->{$b} ?? null;
                    }',
            ],
            'dontMemoizeConditionalAssignment' => [
                'code' => '<?php
                    class A {}

                    class B {
                        protected ?A $a = null;

                        public function test(): void {
                            if (!$this->a) {
                                $this->mayBeSetA();
                            }
                            if ($this->a instanceof A) {
                            }
                        }

                        protected function mayBeSetA(): void {
                            if (mt_rand(0, 1)) {
                                $this->a = new A();
                            }
                        }
                    }',
            ],
            'allowDefaultForTemplatedProperty' => [
                'code' => '<?php
                    /**
                     * @template T as string|null
                     */
                    abstract class A {
                        /** @var list<T> */
                        public $foo = [];
                    }

                    /**
                     * @extends A<string>
                     */
                    class AChild extends A {
                        public $foo = ["hello"];
                    }',
            ],
            'allowBuiltinPropertyDocblock' => [
                'code' => '<?php
                    class FooException extends LogicException {
                        /** @var int */
                        protected $code = 404;
                    }',
            ],
            'dontMemoizeFinalMutationFreeInferredMethod' => [
                'code' => '<?php
                    final class ExecutionMode
                    {
                        private bool $isAutoCommitEnabled = true;

                        public function enableAutoCommit(): void
                        {
                            $this->isAutoCommitEnabled = true;
                        }

                        public function disableAutoCommit(): void
                        {
                            $this->isAutoCommitEnabled = false;
                        }

                        public function isAutoCommitEnabled(): bool
                        {
                            return $this->isAutoCommitEnabled;
                        }
                    }

                    $mode = new ExecutionMode();
                    $mode->disableAutoCommit();
                    assert($mode->isAutoCommitEnabled() === false);

                    $mode->enableAutoCommit();
                    assert($mode->isAutoCommitEnabled() === true);',
            ],
            'promotedInheritedPropertyWithDocblock' => [
                'code' => '<?php
                    abstract class A {
                        /** @var array */
                        public array $arr;
                    }

                    final class B extends A {
                        /** @param array $arr */
                        protected function __construct(public array $arr){}
                    }',
            ],
            'nullsafeShortCircuit' => [
                'code' => '<?php
                    class Foo {
                        private ?self $nullableSelf = null;

                        public function __construct(private self $self) {}

                        public function doBar(): ?self
                        {
                            return $this->nullableSelf?->self->self;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'impossibleIntersection' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    /** @psalm-suppress MissingConstructor */
                    class Baz
                    {
                        private Foo&Bar $foobar;
                    }
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intersectionPropertyAccess' => [
                'code' => '<?php

                    /** @property int $test1 */
                    class a {
                        public function __get(string $name)
                        {
                            return 0;
                        }
                    }

                    /** @var a&object{test2: "lmao"} */
                    $r = null;

                    $test1 = $r->test1;
                    $test2 = $r->test2;',
                'assertions' => [
                    '$test1===' => 'int',
                    '$test2===' => "'lmao'",
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedPropertyAssignment' => [
                'code' => '<?php
                    class A {
                    }

                    (new A)->foo = "cool";',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedPropertyFetch' => [
                'code' => '<?php
                    class A {
                    }

                    echo (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'undefinedThisPropertyAssignment' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {
                            $this->foo = "cool";
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            'undefinedStaticPropertyAssignment' => [
                'code' => '<?php
                    class A {
                        public static function barBar(): void
                        {
                            /** @psalm-suppress UndefinedPropertyFetch */
                            self::$foo = 5;
                        }
                    }',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedThisPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'missingPropertyType' => [
                'code' => '<?php
                    class A {
                        public $foo = null;

                        public function assignToFoo(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInit' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            $this->foo = 5;
                        }

                        public function makeNull(): void {
                            $this->foo = null;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
                'code' => '<?php
                    class A {
                        public $foo = null;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInitConditionallySet' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->foo = 5;
                            }
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'badAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function barBar(): void
                        {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'badStaticAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public static $foo = "a";

                        public static function barBar(): void
                        {
                            self::$foo = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'typeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var B|null */
                        public $foo;

                        public function barBar(A $a): void
                        {
                            $this->foo = $a;
                        }
                    }

                    class B extends A {}',
                'error_message' => 'PropertyTypeCoercion',
            ],
            'mixedTypeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var array<int, A> */
                        public $foo = [];

                        /** @param A[] $arr */
                        public function barBar(array $arr): void
                        {
                            $this->foo = $arr;
                        }
                    }',
                'error_message' => 'MixedPropertyTypeCoercion',
            ],
            'staticTypeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var B|null */
                        public static $foo;

                        public static function barBar(A $a): void
                        {
                            self::$foo = $a;
                        }
                    }

                    class B extends A {}',
                'error_message' => 'PropertyTypeCoercion',
            ],
            'staticMixedTypeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var array<int, A> */
                        public static $foo = [];

                        /** @param A[] $arr */
                        public static function barBar(array $arr): void
                        {
                            self::$foo = $arr;
                        }
                    }',
                'error_message' => 'MixedPropertyTypeCoercion',
            ],
            'possiblyBadAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function barBar(): void
                        {
                            $this->foo = rand(0, 1) ? 5 : "hello";
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'possiblyBadStaticAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public static $foo = "a";

                        public function barBar(): void
                        {
                            self::$foo = rand(0, 1) ? 5 : "hello";
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'badAssignmentAsWell' => [
                'code' => '<?php
                    $a = "hello";
                    $a->foo = "bar";',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'badFetch' => [
                'code' => '<?php
                    $a = "hello";
                    echo $a->foo;',
                'error_message' => 'InvalidPropertyFetch',
            ],
            'possiblyBadFetch' => [
                'code' => '<?php
                    $a = rand(0, 5) > 3 ? "hello" : new stdClass;
                    echo $a->foo;',
                'error_message' => 'PossiblyInvalidPropertyFetch',
            ],
            'mixedPropertyFetch' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    echo $a->foo;',
                'error_message' => 'MixedPropertyFetch',
                'ignored_issues' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'mixedPropertyAssignment' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    $a->foo = "hello";',
                'error_message' => 'MixedPropertyAssignment',
                'ignored_issues' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'possiblyNullablePropertyAssignment' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new Foo() : null;

                    $a->foo = "hello";',
                'error_message' => 'PossiblyNullPropertyAssignment',
            ],
            'nullablePropertyAssignment' => [
                'code' => '<?php
                    $a = null;

                    $a->foo = "hello";',
                'error_message' => 'NullPropertyAssignment',
            ],
            'possiblyNullablePropertyFetch' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new Foo() : null;

                    echo $a->foo;',
                'error_message' => 'PossiblyNullPropertyFetch',
            ],
            'nullablePropertyFetch' => [
                'code' => '<?php
                    $a = null;

                    echo $a->foo;',
                'error_message' => 'NullPropertyFetch',
            ],
            'badArrayProperty' => [
                'code' => '<?php
                    class A {}

                    class B {}

                    class C {
                        /** @var array<B> */
                        public $bb;
                    }

                    $c = new C;
                    $c->bb = [new A, new B];',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'possiblyBadArrayProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int[] */
                        public $bb = [];
                    }

                    class B {
                        /** @var string[] */
                        public $bb;
                    }

                    $c = rand(0, 1) ? new A : new B;
                    $c->bb = ["hello", "world"];',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'notSetInEmptyConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() { }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'noConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'abstractClassInheritsNoConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }

                    class B extends A {}',
                'error_message' => 'MissingConstructor',
            ],
            'abstractClassInheritsPrivateConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        private function __construct() {
                            $this->foo = "hello";
                        }
                    }

                    class B extends A {}

                    $b = new B();',
                'error_message' => 'InaccessibleMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:13',
            ],
            'classInheritsPrivateConstructorWithImplementedConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        private function __construct() {
                            $this->foo = "hello";
                        }
                    }

                    class B extends A {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:11',
            ],
            'notSetInAllBranchesOfIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->a = 5;
                            }
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'propertySetInProtectedMethod' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        protected function foo(): void {
                            $this->a = 5;
                        }
                    }

                    class B extends A {
                        protected function foo() : void {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:15',
            ],
            'definedInTraitNotSetInEmptyConstructor' => [
                'code' => '<?php
                    trait A {
                        /** @var string **/
                        public $a;
                    }
                    class B {
                        use A;

                        public function __construct() {
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:6',
            ],
            'propertySetInPrivateMethodWithIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->foo();
                            }
                        }

                        private function foo(): void {
                            $this->a = 5;
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'privatePropertySameNameNotSetInConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private $b;

                        public function __construct() {
                            $this->b = "foo";
                        }
                    }

                    class B extends A {
                        /** @var string */
                        private $b;
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:13',
            ],
            'privateMethodCalledInParentConstructor' => [
                'code' => '<?php
                    class C extends B {}

                    abstract class B extends A {
                        /** @var string */
                        private $b;

                        /** @var string */
                        protected $c;
                    }

                    class A {
                        public function __construct() {
                            $this->publicMethod();
                        }

                        public function publicMethod() : void {
                            $this->privateMethod();
                        }

                        private function privateMethod() : void {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:2',
            ],
            'privatePropertySetInParentConstructorReversedOrder' => [
                'code' => '<?php
                    class B extends A {
                        /** @var string */
                        private $b;
                    }

                    class A {
                        public function __construct() {
                            if ($this instanceof B) {
                                $this->b = "foo";
                            }
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'privatePropertySetInParentConstructor' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            if ($this instanceof B) {
                                $this->b = "foo";
                            }
                        }
                    }

                    class B extends A {
                        /** @var string */
                        private $b;
                    }

                    ',
                'error_message' => 'InaccessibleProperty',
            ],
            'undefinedPropertyClass' => [
                'code' => '<?php
                    class A {
                        /** @var B */
                        public $foo;
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'abstractClassWithNoConstructorButChild' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }

                    class B extends A {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'badAssignmentToUndefinedVars' => [
                'code' => '<?php
                    $x->$y = 4;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'echoUndefinedPropertyFetch' => [
                'code' => '<?php
                    echo $x->$y;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'toStringPropertyAssignment' => [
                'code' => '<?php
                    class A {
                      /** @var ?string */
                      public $foo;
                    }

                    class B {
                      public function __toString() {
                        return "bar";
                      }
                    }

                    $a = new A();
                    $a->foo = new B;',
                'error_message' => 'ImplicitToStringCast',
            ],
            'noInfiniteLoop' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            $this->doThing();
                        }

                        private function doThing(): void {
                            if (rand(0, 1)) {
                                $this->doOtherThing();
                            }
                        }

                        private function doOtherThing(): void {
                            if (rand(0, 1)) {
                                $this->doThing();
                            }
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'invalidPropertyDefault' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = "hello";
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'prohibitMixedAssignmentNormally' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         */
                        private $mixed;

                        /**
                         * @param mixed $value
                         */
                        public function setMixed($value): void
                        {
                            $this->mixed = $value;
                        }
                    }',
                'error_message' => 'MixedAssignment',
            ],
            'assertPropertyTypeHasImpossibleType' => [
                'code' => '<?php
                    class A {
                        /** @var ?B */
                        public $foo;
                    }
                    class B {}
                    $a = new A();
                    if (is_string($a->foo)) {}',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'impossiblePropertyCheck' => [
                'code' => '<?php
                    class Bar {}
                    class Foo {
                        /** @var Bar */
                        private $bar;

                        public function __construct() {
                            $this->bar = new Bar();
                        }

                        public function getBar(): void {
                            if (!$this->bar) {}
                        }
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'staticPropertyOfStaticTypeMethodCallWithUndefinedMethod' => [
                'code' => '<?php
                    class A {
                        /** @var static|null */
                        public $instance;

                        public function foo() : void {
                            if ($this->instance) {
                                $this->instance->bar();
                            }
                        }
                    }

                    class B extends A {
                        public function bar() : void {}
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'misnamedPropertyByVariable' => [
                'code' => '<?php
                    class B {
                        /** @var string|null */
                        public $foo;

                        public function bar(string $var_name) : ?string {
                            if ($var_name === "bar") {
                                return $this->$var_name;
                            }

                            return null;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'inheritDocPropertyTypesIncorrectAssignmentToInstanceProperty' => [
                'code' => '<?php
                    class X {
                        /**
                         * @var string|null
                         */
                        public $a;
                    }

                    class Y extends X {
                        public $a = "foo";
                    }

                    (new Y)->a = 5;
                    echo (new Y)->a;
                    Y::$b = "bar";
                    echo Y::$b;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'inheritDocPropertyTypesIncorrectAssignmentToStaticProperty' => [
                'code' => '<?php
                    class X {
                        /**
                         * @var string|null
                         */
                        public static $b;
                    }

                    class Y extends X {
                        public static $b = "foo";
                    }

                    Y::$b = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'uninitializedProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'uninitializedPropertyWithoutType' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'UninitializedProperty',
                'ignored_issues' => ['MixedArgument', 'MissingPropertyType'],
            ],
            'uninitializedObjectProperty' => [
                'code' => '<?php
                    class Foo {
                        /** @var int */
                        public $bar = 5;
                    }
                    function takesInt(int $i) : void {}
                    class A {
                        /** @var Foo */
                        public $foo;

                        public function __construct(Foo $foo) {
                            takesInt($this->foo->bar);
                            $this->foo = $foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'possiblyNullArg' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'noCrashOnMagicCall' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private $a;

                        public function __construct() {
                            $this->setA();
                        }

                        public function __call(string $var, array $args) {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'reportGoodLocationForPropertyError' => [
                'code' => '<?php
                    class C {
                        /** @var string */
                        public $s;

                        public function __construct() {
                            $this->setS();
                        }

                        public function setS() : void {
                            $this->s = "hello";
                        }
                    }

                    class D extends C {
                        public function setS() : void {
                            // nothing happens here
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:15',
            ],
            'noCrashWhenUnsettingPropertyWithoutDefaultInConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        private $foo;

                        public function __construct() {
                            unset($this->foo);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'promotedPropertyNotSetInExtendedConstructor' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(
                            public string $name,
                        ) {}
                    }

                    class B extends A
                    {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullableTypedPropertyNoConstructor' => [
                'code' => '<?php
                    class A {
                        private ?bool $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'nullableTypedPropertyEmptyConstructor' => [
                'code' => '<?php
                    class A {
                        private ?bool $foo;

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'nullableTypedPropertyUseBeforeInitialised' => [
                'code' => '<?php
                    class A {
                        private ?bool $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'nullableTypedPropertyNoConstructorWithDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'nullableTypedPropertyEmptyConstructorWithDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'nullableTypedPropertyUseBeforeInitialisedWithDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'badStaticPropertyDefault' => [
                'code' => '<?php
                    class TestStatic {
                        /**
                         * @var array<string, bool>
                         */
                        public static $test = ["string-key" => 1];
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'addNullToMixedAfterNullablePropertyFetch' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        public $foo;
                    }

                    function takesString(string $s) : void {}

                    function takesA(?A $a) : void {
                        /**
                         * @psalm-suppress PossiblyNullPropertyFetch
                         * @psalm-suppress MixedArgument
                         */
                        takesString($a->foo);
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'catchBadArrayStaticProperty' => [
                'code' => '<?php
                    namespace Bar;

                    class Foo {}
                    class A {
                        /** @var array<string, object> */
                        public array $map = [];

                        /**
                         * @param string $class
                         */
                        public function get(string $class) : void {
                            $this->map[$class] = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'preventArrayPushOnArrayValue' => [
                'code' => '<?php
                    class MyClass {
                        /**
                         * @var int[]
                         */
                        private $prop = [];

                        /**
                         * @return void
                         */
                        public function foo() {
                            array_push($this->prop, "bad");
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'overriddenConstructorCalledMethod' => [
                'code' => '<?php
                    class ParentClass {
                        private string $prop;

                        public function __construct() {
                            $this->init();
                        }

                        public function init(): void {
                            $this->prop = "zxc";
                        }
                    }

                    class ChildClass extends ParentClass {
                        public function init(): void {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'propertyWithSameNameUndefined' => [
                'code' => '<?php
                    class Foo {}

                    class Bar {
                        public int $id = 3;

                        public function __construct(Foo $model) {
                            echo $model->id;
                        }
                    }',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'missingPropertyTypeWithDocblock' => [
                'code' => '<?php
                    class C {
                        /**
                         * @varr int
                         */
                        public $var;
                    }',
                'error_message' => 'MissingPropertyType',
            ],
            'promotedPrivateProperty' => [
                'code' => '<?php
                    class A {
                        public function __construct(private int $foo = 5) {}
                    }

                    echo (new A)->foo;',
                'error_message' => 'InaccessibleProperty',
            ],
            'overwritePropertyType' => [
                'code' => '<?php
                    class A {
                        /** @var array */
                        public string $s = [];
                    }',
                'error_message' => 'MismatchingDocblockPropertyType',
            ],
            'possiblyNullOnFunctionCallNotCoalesced' => [
                'code' => '<?php
                    function getC() : ?C {
                        return rand(0, 1) ? new C() : null;
                    }

                    function foo() : void {
                        echo getC()->id;
                    }

                    class C {
                        public int $id = 1;
                    }',
                'error_message' => 'PossiblyNullPropertyFetch',
            ],
            'noCrashWhenCallingMagicSet' => [
                'code' => '<?php
                    class A {
                        public function __set(string $s, mixed $value) : void {}
                    }

                    (new A)->__set("foo");',
                'error_message' => 'TooFewArguments',
            ],
            'noCrashWhenCallingMagicGet' => [
                'code' => '<?php
                    class A {
                        public function __get(string $s) : mixed {}
                    }

                    (new A)->__get();',
                'error_message' => 'TooFewArguments',
            ],
            'staticReadOfNonStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $prop = 1;
                    }
                    echo A::$prop;
                ',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'staticWriteToNonStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $prop = 1;
                    }
                    A::$prop = 42;
                ',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'nonStaticReadOfStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public static $prop = 1;
                    }
                    echo (new A)->prop;
                ',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'nonStaticWriteToStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public static $prop = 1;
                    }
                    (new A)->prop = 42;
                ',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'nativeMixedPropertyWithNoConstructor' => [
                'code' => <<< 'PHP'
                    <?php
                    class A {
                        public mixed $foo;
                    }
                PHP,
                'error_message' => 'MissingConstructor',
            ],
        ];
    }
}
