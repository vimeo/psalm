<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;
use Psalm\Config;
use Psalm\Context;

class PropertyTypeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    public function testForgetPropertyAssignments(): void
    {
        $this->expectExceptionMessage('NullableReturnStatement');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
                    /** @var ?int **/
                    public $x;

                    public function getX(): int {
                        $this->x = 5;

                        XCollector::modify();

                        return $this->x;
                    }
                }'
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
                    /** @var ?int **/
                    public $x;

                    public function getX(): int {
                        $this->x = 5;

                        XCollector::modify();

                        return $this->x;
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsInBranchWithThrow(): void
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCrates(): void
    {
        /** @var class-string $classString */
        $classString = 'Foo';
        Config::getInstance()->addUniversalObjectCrate($classString);

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo { }

                $f = new Foo();
                // reads are fine
                $f->bar;

                // sets are fine
                $f->buzz = false;
        '
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'newVarInIf' => [
                '<?php
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
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    $a = (new A)->foo;',
                'assertions' => [],
                'error_levels' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'propertyWithoutTypeSuppressingIssueAndAssertingNull' => [
                '<?php
                    class A {
                        /** @return void */
                        function foo() {
                            $boop = $this->foo === null && rand(0,1);

                            echo $this->foo->baz;
                        }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedThisPropertyFetch',
                    'MixedAssignment',
                    'MixedArgument',
                    'MixedMethodCall',
                    'MixedPropertyFetch',
                ],
            ],
            'sharedPropertyInIf' => [
                '<?php
                    class A {
                        /** @var int */
                        public $foo = 0;
                    }
                    class B {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new A(): (rand(0, 10) ? new B(): null);
                    $b = null;

                    if ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'int|null|string',
                ],
            ],
            'sharedPropertyInElseIf' => [
                '<?php
                    class A {
                        /** @var int */
                        public $foo = 0;
                    }
                    class B {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new A(): new B();
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
                '<?php
                    class A {
                        /** @var string */
                        public $aa = "";
                    }

                    class B {
                        /** @var A|null */
                        public $bb;
                    }

                    $b = rand(0, 10) ? new A(): new B();

                    if ($b instanceof B && isset($b->bb) && $b->bb->aa === "aa") {
                        echo $b->bb->aa;
                    }',
            ],
            'nullablePropertyAfterGuard' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $aa;
                    }

                    $a = new A();

                    if (!$a->aa) {
                        $a->aa = "hello";
                    }

                    echo substr($a->aa, 1);',
            ],
            'nullableStaticPropertyWithIfCheck' => [
                '<?php
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
                '<?php
                    class Foo {
                    }

                    $a = new \ReflectionMethod(Foo::class, "__construct");

                    echo $a->name . " - " . $a->class;',
            ],
            'grandparentReflectedProperties' => [
                '<?php
                    $a = new DOMElement("foo");
                    $owner = $a->ownerDocument;',
                'assertions' => [
                    '$owner' => 'DOMDocument|null',
                ],
            ],
            'propertyMapHydration' => [
                '<?php
                    function foo(DOMElement $e) : void {
                        echo $e->attributes->length;
                    }',
            ],
            'genericTypeFromPropertyMap' => [
                '<?php
                    function foo(DOMElement $e) : ?DOMAttr {
                        return $e->attributes->item(0);
                    }'
            ],
            'goodArrayProperties' => [
                '<?php
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
                'error_levels' => ['MixedAssignment'],
            ],
            'issetPropertyDoesNotExist' => [
                '<?php
                    class A {
                    }

                    $a = new A();

                    if (isset($a->bar)) {

                    }',
            ],
            'notSetInConstructorButHasDefault' => [
                '<?php
                    class A {
                        /** @var int */
                        public $a = 0;

                        public function __construct() { }
                    }',
            ],
            'propertySetInPrivateMethod' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }',
            ],
            'abstractClassConstructorAndChildConstructor' => [
                '<?php
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
                '<?php
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
                '<?php
                    /** @psalm-suppress PropertyNotSetInConstructor */
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() { }
                    }',
            ],
            'extendsClassWithPrivateConstructorSet' => [
                '<?php
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
                '<?php
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
                '<?php
                    interface I {
                        public function foo(): void;
                    }

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
                'assertions' => [],
                'error_levels' => [
                    'PropertyNotSetInConstructor' => Config::REPORT_INFO,
                ],
            ],
            'callsPrivateParentMethodThenUsesParentInitializedProperty' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    namespace PhpParser\Node\Stmt;

                    use PhpParser\Node;

                    class Finally_ extends Node\Stmt
                    {
                        /** @var Node[] Statements */
                        public $stmts;

                        /**
                         * Constructs a finally node.
                         *
                         * @param array<int, Node\Stmt> $stmts      Statements
                         * @param array  $attributes Additional attributes
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                'error_levels' => [
                    'MixedAssignment',
                ],
            ],
            'propertyAssignmentToMixed' => [
                '<?php
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
                'error_levels' => [
                    'MixedAssignment',
                ],
            ],
            'propertySetInBothIfBranches' => [
                '<?php
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
                '<?php
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
            'allowMixedAssignmetWhenDesired' => [
                '<?php
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
                '<?php
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
                '<?php
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
            'setPropertiesOfSpecialObjects' => [
                '<?php
                    $a = new stdClass();
                    $a->b = "c";

                    $d = new SimpleXMLElement("<person><child role=\"son\"></child></person>");
                    $d->e = "f";',
                'assertions' => [
                    '$a' => 'stdClass',
                    '$a->b' => 'string',
                    '$d' => 'SimpleXMLElement',
                    '$d->e' => 'mixed',
                ],
            ],
            'allowLessSpecificReturnTypeForOverriddenMethod' => [
                '<?php
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
                '<?php
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
                '<?php
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

                    $a = new A();

                    if ($a->instance) {
                        $a->instance->bar();
                        echo $a->instance->bat;
                    }',
            ],
            'nonStaticPropertyMethodCall' => [
                '<?php
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
                '<?php
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
                '<?php
                    class C {
                        /** @psalm-var array<class-string, int> */
                        public $member = [
                            InvalidArgumentException::class => 1,
                        ];
                    }',
            ],
            'allowPrivatePropertySetAfterInstanceof' => [
                '<?php
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
                '<?php
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
                '<?php
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
                [],
                'error_levels' => [],
            ],
            'inheritDocPropertyTypes' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class Foo {
                        /** @var self */
                        public static $current;
                    }

                    $a = Foo::$current;',
                [
                    '$a' => 'Foo',
                ],
            ],
            'noMixedErrorWhenAssignmentExpectsMixed' => [
                '<?php
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
                '<?php
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
                '<?php
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
            'unitializedPropertySuppressPropertyNotSetInConstructor' => [
                '<?php
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
                [],
                ['PropertyNotSetInConstructor'],
            ],
            'setTKeyedArrayPropertyType' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'parentSetsWiderTypeInConstructor' => [
                '<?php
                    interface Foo {}

                    interface FooMore extends Foo {
                        public function something(): void;
                    }

                    class Bar {
                        /** @var Foo */
                        protected $foo;

                        public function __construct(Foo $foo) {
                            $this->foo = $foo;
                        }
                    }


                    class BarMore extends Bar {
                        /** @var FooMore */
                        protected $foo;

                        public function __construct(FooMore $foo) {
                            parent::__construct($foo);
                            $this->foo->something();
                        }
                    }',
            ],
            'inferPropertyTypesForSimpleConstructors' => [
                '<?php
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
                '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;
                    }',
            ],
            'nullableDocblockTypedPropertyEmptyConstructor' => [
                '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;

                        public function __construct() {}
                    }',
            ],
            'nullableDocblockTypedPropertyUseBeforeInitialised' => [
                '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
            ],
            'dontAlterClosureParams' => [
                '<?php
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
                '<?php
                    class Tag {}
                    class EntityTags {
                        private $tags;

                        public function __construct(Tag ...$tags) {
                            $this->tags = $tags;
                        }
                    }',
            ],
            'readonlyPropertySetInConstructor' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new A)->bar;'
            ],
            'readonlyWithPrivateMutationsAllowedPropertySetInAnotherMEthod' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         * @psalm-allow-private-mutation
                         */
                        public ?string $bar = null;

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;'
            ],
            'readonlyPublicPropertySetInAnotherMEthod' => [
                '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         */
                        public ?string $bar = null;

                        public function setBar(string $s) : void {
                            $this->bar = $s;
                        }
                    }

                    echo (new A)->bar;'
            ],
            'readonlyPropertySetChildClass' => [
                '<?php
                    abstract class A {
                        /**
                         * @readonly
                         */
                        public string $bar;
                    }

                    class B extends A {
                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    echo (new B)->bar;'
            ],
            'staticPropertyDefaultWithStaticType' => [
                '<?php
                    class Test {
                        /** @var array<int, static> */
                        private static $t1 = [];

                        /** @var array<int, static> */
                        private $t2 = [];
                    }'
            ],
            'propagateIgnoreNullableOnPropertyFetch' => [
                '<?php
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
                '<?php

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
                '<?php
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
                    }'
            ],
            'testRemoveClauseAfterReassignment' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'allowGoodArrayPushOnArrayValue' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'noConditionalCallToParentConstructor' => [
                '<?php
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
                    }'
            ],
            'allowByReferenceAssignmentToUninitializedNullableProperty' => [
                '<?php
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
                    }'
            ],
            'dontCarryAssertionsOver' => [
                '<?php
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
                    }'
            ],
            'useVariableAccessInStatic' => [
                '<?php
                    class A2 {
                        public static string $title = "foo";
                        public static string $label = "bar";
                    }

                    $model = new A2();
                    $message = $model::$title;
                    $message .= $model::$label;
                    echo $message;'
            ],
            'staticPropertyInFinalMethod' => [
                '<?php
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
                    }'
            ],
            'aliasedFinalMethod' => [
                '<?php
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
                    }'
            ],
            'aliasedAsFinalMethod' => [
                '<?php
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
                    }'
            ],
            'staticPropertyAssertion' => [
                '<?php
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
                    }'
            ],
            'dontMemoizePropertyTypeAfterRootVarAssertion' => [
                '<?php
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
                    }'
            ],
            'unionPropertyType' => [
                '<?php
                    class A {
                        public string|int $i;

                        public function __construct() {
                            $this->i = 5;
                            $this->i = "hello";
                        }
                    }

                    $a = new A();

                    if ($a->i === 3) {}
                    if ($a->i === "foo") {}'
            ],
            'setClassStringOfStatic' => [
                '<?php
                    class A {
                        public static array $stack = [];

                        public static function foo() : void {
                            $class = get_called_class();
                            $class::$stack[] = 1;
                        }
                    }'
            ],
            'promotedPublicPropertyWithDefault' => [
                '<?php
                    class A {
                        public function __construct(public int $foo = 5) {}
                    }

                    echo (new A)->foo;'
            ],
            'promotedPublicPropertyWitoutDefault' => [
                '<?php
                    class A {
                        public function __construct(public int $foo) {}
                    }

                    echo (new A(5))->foo;'
            ],
            'promotedProtectedProperty' => [
                '<?php
                    class A {
                        public function __construct(protected int $foo) {}
                    }

                    class AChild extends A {
                        public function bar() : int {
                            return $this->foo;
                        }
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedPropertyAssignment' => [
                '<?php
                    class A {
                    }

                    (new A)->foo = "cool";',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedPropertyFetch' => [
                '<?php
                    class A {
                    }

                    echo (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'undefinedThisPropertyAssignment' => [
                '<?php
                    class A {
                        public function fooFoo(): void {
                            $this->foo = "cool";
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            'undefinedStaticPropertyAssignment' => [
                '<?php
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
                '<?php
                    class A {
                        public function fooFoo(): void {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'missingPropertyType' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    $a = "hello";
                    $a->foo = "bar";',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'badFetch' => [
                '<?php
                    $a = "hello";
                    echo $a->foo;',
                'error_message' => 'InvalidPropertyFetch',
            ],
            'possiblyBadFetch' => [
                '<?php
                    $a = rand(0, 5) > 3 ? "hello" : new stdClass;
                    echo $a->foo;',
                'error_message' => 'PossiblyInvalidPropertyFetch',
            ],
            'mixedPropertyFetch' => [
                '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    echo $a->foo;',
                'error_message' => 'MixedPropertyFetch',
                'error_levels' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'mixedPropertyAssignment' => [
                '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    $a->foo = "hello";',
                'error_message' => 'MixedPropertyAssignment',
                'error_levels' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'possiblyNullablePropertyAssignment' => [
                '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new Foo(): null;

                    $a->foo = "hello";',
                'error_message' => 'PossiblyNullPropertyAssignment',
            ],
            'nullablePropertyAssignment' => [
                '<?php
                    $a = null;

                    $a->foo = "hello";',
                'error_message' => 'NullPropertyAssignment',
            ],
            'possiblyNullablePropertyFetch' => [
                '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new Foo(): null;

                    echo $a->foo;',
                'error_message' => 'PossiblyNullPropertyFetch',
            ],
            'nullablePropertyFetch' => [
                '<?php
                    $a = null;

                    echo $a->foo;',
                'error_message' => 'NullPropertyFetch',
            ],
            'badArrayProperty' => [
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() { }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'noConstructor' => [
                '<?php
                    class A {
                        /** @var int */
                        public $a;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'abstractClassInheritsNoConstructor' => [
                '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }

                    class B extends A {}',
                'error_message' => 'MissingConstructor',
            ],
            'abstractClassInheritsPrivateConstructor' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        /** @var B */
                        public $foo;
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'abstractClassWithNoConstructorButChild' => [
                '<?php
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
                '<?php
                    $x->$y = 4;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'echoUndefinedPropertyFetch' => [
                '<?php
                    echo $x->$y;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'toStringPropertyAssignment' => [
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        /** @var int */
                        public $a = "hello";
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'prohibitMixedAssignmentNormally' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'unitializedProperty' => [
                '<?php
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
            'unitializedPropertyWithoutType' => [
                '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'UninitializedProperty',
                ['MixedArgument', 'MissingPropertyType']
            ],
            'unitializedObjectProperty' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        /** @var bool */
                        private $foo;

                        public function __construct() {
                            unset($this->foo);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'parentSetsWiderTypeInConstructor' => [
                '<?php
                    interface Foo {}

                    interface FooMore extends Foo {}

                    class Bar {
                        /** @var Foo */
                        protected $foo;

                        public function __construct(Foo $foo) {
                            $this->foo = $foo;
                        }
                    }

                    class BarMore extends Bar {
                        /** @var FooMore */
                        protected $foo;

                        public function __construct(FooMore $foo) {
                            parent::__construct($foo);
                            $this->foo->something();
                        }
                    }',
                'error_message' => 'UndefinedInterfaceMethod',
            ],
            'nullableTypedPropertyNoConstructor' => [
                '<?php
                    class A {
                        private ?bool $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'nullableTypedPropertyEmptyConstructor' => [
                '<?php
                    class A {
                        private ?bool $foo;

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'nullableTypedPropertyUseBeforeInitialised' => [
                '<?php
                    class A {
                        private ?bool $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'nullableTypedPropertyNoConstructorWithDocblock' => [
                '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'nullableTypedPropertyEmptyConstructorWithDocblock' => [
                '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'nullableTypedPropertyUseBeforeInitialisedWithDocblock' => [
                '<?php
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
                '<?php
                    class TestStatic {
                        /**
                         * @var array<string, bool>
                         */
                        public static $test = ["string-key" => 1];
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue'
            ],
            'readonlyPropertySetInConstructorAndAlsoAnotherMethodInsideClass' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }

                        public function setBar() : void {
                            $this->bar = "goodbye";
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'readonlyPropertySetInConstructorAndAlsoAnotherMethodInSublass' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    class B extends A {
                        public function setBar() : void {
                            $this->bar = "hello";
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'readonlyPropertySetInConstructorAndAlsoOutsideClass' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty',
            ],
            'readonlyPropertySetInConstructorAndAlsoOutsideClassWithAllowPrivate' => [
                '<?php
                    class A {
                        /**
                         * @readonly
                         * @psalm-allow-private-mutation
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }

                        public function setAgain() : void {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:21',
            ],
            'readonlyPublicPropertySetInConstructorAndAlsoOutsideClass' => [
                '<?php
                    class A {
                        /**
                         * @psalm-readonly-allow-private-mutation
                         */
                        public string $bar;

                        public function __construct() {
                            $this->bar = "hello";
                        }

                        public function setAgain() : void {
                            $this->bar = "hello";
                        }
                    }

                    $a = new A();
                    $a->bar = "goodbye";',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:21',
            ],
            'addNullToMixedAfterNullablePropertyFetch' => [
                '<?php
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
                '<?php
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
                'error_message' => 'InvalidPropertyAssignmentValue'
            ],
            'preventArrayPushOnArrayValue' => [
                '<?php
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
                'error_message' => 'InvalidPropertyAssignmentValue'
            ],
            'overriddenConstructorCalledMethod' => [
                '<?php
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
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'propertyWithSameNameUndefined' => [
                '<?php
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
                '<?php
                    class C {
                        /**
                         * @varr int
                         */
                        public $var;
                    }',
                'error_message' => 'MissingPropertyType',
            ],
            'promotedPrivateProperty' => [
                '<?php
                    class A {
                        public function __construct(private int $foo = 5) {}
                    }

                    echo (new A)->foo;',
                'error_message' => 'InaccessibleProperty',
            ],
        ];
    }
}
