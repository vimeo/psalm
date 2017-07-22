<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;

class PropertyTypeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidReturnType
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
                        if ($this->x === null) {
                            $this->x = 0;
                        }
                        $this->modifyX();
                        return $this->x;
                    }

                    private function modifyX(): void {
                        $this->x = null;
                    }
                }'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
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

                    $a = rand(0, 10) ? new A() : (rand(0, 10) ? new B() : null);
                    $b = null;

                    if ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'null|string|int',
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

                    $a = rand(0, 10) ? new A() : new B();
                    $b = null;

                    if (rand(0, 10) === 4) {
                        // do nothing
                    }
                    elseif ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'null|string|int',
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

                    $b = rand(0, 10) ? new A() : new B();

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

                        public static function getFoo() : A {
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

                        private function foo() : void {
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

                        private function foo() : void {
                            $this->bar();
                        }

                        private function bar() : void {
                            $this->a = 5;
                        }
                    }',
            ],
            'propertyArrayIssetAssertion' => [
                '<?php
                    function bar(string $s) : void { }

                    class A {
                        /** @var array<string, string> */
                        public $a = [];

                        private function foo() : void {
                            if (isset($this->a["hello"])) {
                                bar($this->a["hello"]);
                            }
                        }
                    }',
            ],
            'propertyArrayIssetAssertionWithVariableOffset' => [
                '<?php
                    function bar(string $s) : void { }

                    class A {
                        /** @var array<string, string> */
                        public $a = [];

                        private function foo() : void {
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
                    function bar(string $s) : void { }

                    class A {
                        /** @var array<string, string> */
                        public static $a = [];
                    }

                    function foo() : void {
                        $b = "hello";

                        if (!isset(A::$a[$b])) {
                            return;
                        }

                        bar(A::$a[$b]);
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
                        public function fooFoo() : void {
                            $this->foo = "cool";
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            'undefinedThisPropertyFetch' => [
                '<?php
                    class A {
                        public function fooFoo() : void {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'missingPropertyDeclaration' => [
                '<?php
                    class A {
                    }

                    /** @psalm-suppress UndefinedPropertyAssignment */
                    function fooDo() : void {
                        (new A)->foo = "cool";
                    }',
                'error_message' => 'MissingPropertyDeclaration',
            ],
            'missingPropertyType' => [
                '<?php
                    class A {
                        public $foo;

                        public function assignToFoo() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider null|int',
            ],
            'missingPropertyTypeWithConstructorInit' => [
                '<?php
                    class A {
                        public $foo;

                        public function __construct() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
                '<?php
                    class A {
                        public $foo;

                        public function __construct() : void {
                            $this->foo = 5;
                        }

                        public function makeNull() : void {
                            $this->foo = null;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider null|int',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
                '<?php
                    class A {
                        public $foo = null;

                        public function __construct() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'badAssignment' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function barBar() : void
                        {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignment',
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
                'error_message' => '',
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

                    $a = rand(0, 10) ? new Foo() : null;

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

                    $a = rand(0, 10) ? new Foo() : null;

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
                'error_message' => 'InvalidPropertyAssignment',
                'error_levels' => ['MixedAssignment'],
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
                'error_message' => 'InaccessibleMethod',
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

                        protected function foo() : void {
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

                        private function foo() : void {
                            $this->a = 5;
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
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

                        private function foo() : void {
                            $this->a = 5;
                        }

                        private function bar() : void {
                            $this->a = 5;
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
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
        ];
    }
}
