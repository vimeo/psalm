<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class PropertyTypeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage NullableReturnStatement
     *
     * @return                   void
     */
    public function testForgetPropertyAssignments()
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class X {
                    /** @var ?int **/
                    private $x;

                    public function getX(): int {
                        $this->x = 5;

                        $this->modifyX();

                        return $this->x;
                    }

                    private function modifyX(): void {
                        $this->x = null;
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return                   void
     */
    public function testForgetPropertyAssignmentsInBranchWithThrow()
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class X {
                    /** @var ?int **/
                    private $x;

                    public function getX(): int {
                        $this->x = 5;

                        if (rand(0, 1)) {
                            $this->modifyX();
                            throw new \Exception("bad");
                        }

                        return $this->x;
                    }

                    private function modifyX(): void {
                        $this->x = null;
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testRemovePropertyAfterReassignment()
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @var A|null */
                    public $parent;

                    public function __construct() {
                        $this->parent = rand(0, 1) ? new A : null;
                    }
                }

                $a = new A();

                if ($a->parent === null) {
                    throw new \Exception("bad");
                }

                $a = $a->parent;'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('A', (string) $context->vars_in_scope['$a']);
        $this->assertFalse(isset($context->vars_in_scope['$a->parent']));
    }

    /**
     * @return void
     */
    public function testRemoveClauseAfterReassignment()
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'newVarInIf' => [
                '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        public $foo;

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
                        public $foo;
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
                    '$b' => 'null|int|string',
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
                    '$b' => 'null|int|string',
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

                    $a = new \ReflectionMethod("Foo", "__construct");

                    echo $a->name . " - " . $a->class;',
            ],
            'grandparentReflectedProperties' => [
                '<?php
                    $a = new DOMElement("foo");
                    $owner = $a->ownerDocument;',
                'assertions' => [
                    '$owner' => 'DOMDocument',
                ],
            ],
            'propertyMapHydration' => [
                '<?php
                    function foo(DOMElement $e) : void {
                        echo $e->attributes->length;
                    }',
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
                        public function foo(): void
                        {
                            $this->bar = "hello";
                        }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'PropertyNotSetInConstructor' => Config::REPORT_INFO,
                ],
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
                    '$next' => 'null|Node',
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
                         * @param Node[] $stmts      Statements
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
                'error_levels' => [
                    'MixedTypeCoercion',
                    'MissingReturnType',
                ],
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
                        private $mixed;

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
                    }'
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
                    }'
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
                    }'
            ],
            'classStringPropertyType' => [
                '<?php
                    class C {
                        /** @psalm-var array<class-string, int> */
                        public $member = [
                            InvalidArgumentException::class => 1,
                        ];
                    }'
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
                'error_levels' => []
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
                ]
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
                    }'
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
                    }'
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
                    class C extends B {}'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
                        public $foo;

                        public function assignToFoo(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property A::$foo does not have a ' .
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
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property A::$foo does not have a ' .
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
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property A::$foo does not have a ' .
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
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Property A::$foo does not have a ' .
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
                'error_message' => 'TypeCoercion',
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
                'error_message' => 'MixedTypeCoercion',
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
                'error_message' => 'TypeCoercion',
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
                'error_message' => 'MixedTypeCoercion',
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
                'error_message' => 'PropertyNotSetInConstructor',
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

                    class B extends A {}',
                'error_message' => 'InaccessibleMethod - src/somefile.php:11',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'UndefinedClass',
            ],
            'abstractClassWithNoConstructorButChild' => [
                '<?php
                    abstract class A {
                      /** @var string */
                      public $foo;
                    }

                    class B extends A {
                      public function __construct() {
                      }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
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
                'error_message' => 'PropertyNotSetInConstructor',
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
        ];
    }
}
