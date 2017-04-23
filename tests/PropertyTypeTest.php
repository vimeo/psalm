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
     * @return                   void
     */
    public function testForgetPropertyAssignments()
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'new-var-in-if' => [
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
                    }'
            ],
            'property-without-type-suppressing-issue' => [
                '<?php
                    class A {
                        public $foo;
                    }
            
                    $a = (new A)->foo;',
                'assertions' => [],
                'error_levels' => [
                    'MissingPropertyType',
                    'MixedAssignment'
                ]
            ],
            'property-without-type-suppressing-issue-and-asserting-null' => [
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
                    'MixedPropertyFetch'
                ]
            ],
            'shared-property-in-if' => [
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
                    ['null|string|int' => '$b']
                ]
            ],
            'shared-property-in-else-if' => [
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
                    ['null|string|int' => '$b']
                ]
            ],
            'nullable-property-check' => [
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
                    }'
            ],
            'nullable-property-after-guard' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $aa;
                    }
            
                    $a = new A();
            
                    if (!$a->aa) {
                        $a->aa = "hello";
                    }
            
                    echo substr($a->aa, 1);'
            ],
            'nullable-static-property-with-if-check' => [
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
                    }'
            ],
            'reflection-properties' => [
                '<?php
                    class Foo {
                    }
            
                    $a = new \ReflectionMethod("Foo", "__construct");
            
                    echo $a->name . " - " . $a->class;'
            ],
            'grandparent-reflected-properties' => [
                '<?php
                    $a = new DOMElement("foo");
                    $owner = $a->ownerDocument;',
                'assertions' => [
                    ['DOMDocument' => '$owner']
                ]
            ],
            'good-array-properties' => [
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
                'error_levels' => ['MixedAssignment']
            ],
            'isset-property-does-not-exist' => [
                '<?php
                    class A {
                    }
            
                    $a = new A();
            
                    if (isset($a->bar)) {
            
                    }'
            ],
            'not-set-in-constructor-but-has-default' => [
                '<?php
                    class A {
                        /** @var int */
                        public $a = 0;
        
                        public function __construct() { }
                    }'
            ],
            'property-set-in-private-method' => [
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
                    }'
            ],
            'defined-in-trait-set-in-constructor' => [
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
                    }'
            ],
            'property-set-in-nested-private-method' => [
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
                    }'
            ],
            'property-array-isset-assertion' => [
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
                    }'
            ],
            'property-array-isset-assertion-with-variable-offset' => [
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
                    }'
            ],
            'static-property-array-isset-assertion-with-variable-offset' => [
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
                    }'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'undefined-property-assignment' => [
                '<?php
                    class A {
                    }
            
                    (new A)->foo = "cool";',
                'error_message' => 'UndefinedPropertyAssignment'
            ],
            'undefined-property-fetch' => [
                '<?php
                    class A {
                    }
            
                    echo (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch'
            ],
            'undefined-this-property-assignment' => [
                '<?php
                    class A {
                        public function fooFoo() : void {
                            $this->foo = "cool";
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment'
            ],
            'undefined-this-property-fetch' => [
                '<?php
                    class A {
                        public function fooFoo() : void {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch'
            ],
            'missing-property-declaration' => [
                '<?php
                    class A {
                    }
            
                    /** @psalm-suppress UndefinedPropertyAssignment */
                    function fooDo() : void {
                        (new A)->foo = "cool";
                    }',
                'error_message' => 'MissingPropertyDeclaration'
            ],
            'missing-property-type' => [
                '<?php
                    class A {
                        public $foo;
            
                        public function assignToFoo() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider null|int'
            ],
            'missing-property-type-with-constructor-init' => [
                '<?php
                    class A {
                        public $foo;
            
                        public function __construct() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider int'
            ],
            'missing-property-type-with-constructor-init-and-null' => [
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
                    'declared type - consider null|int'
            ],
            // Skipped. Doesn't yet work.
            'SKIPPED-missing-property-type-with-constructor-init-in-private-method' => [
                '<?php
                    class A {
                        public $foo;
            
                        public function __construct() : void {
                            $this->makeValue();
                        }
            
                        private function makeValue() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider int'
            ],
            'missing-property-type-with-constructor-init-and-null-default' => [
                '<?php
                    class A {
                        public $foo = null;
            
                        public function __construct() : void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - somefile.php:3 - Property A::$foo does not have a ' .
                    'declared type - consider int|null'
            ],
            'bad-assignment' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo;
                
                        public function barBar() : void
                        {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignment'
            ],
            'bad-assignment-as-well' => [
                '<?php
                    $a = "hello";
                    $a->foo = "bar";',
                'error_message' => 'InvalidPropertyAssignment'
            ],
            'bad-fetch' => [
                '<?php
                    $a = "hello";
                    echo $a->foo;',
                'error_message' => 'InvalidPropertyFetch'
            ],
            'mixed-property-fetch' => [
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
                    'MixedAssignment'
                ]
            ],
            'mixed-property-assignment' => [
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
                    'MixedAssignment'
                ]
            ],
            'possibly-nullable-property-assignment' => [
                '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }
            
                    $a = rand(0, 10) ? new Foo() : null;
            
                    $a->foo = "hello";',
                'error_message' => 'PossiblyNullPropertyAssignment'
            ],
            'nullable-property-assignment' => [
                '<?php
                    $a = null;
            
                    $a->foo = "hello";',
                'error_message' => 'NullPropertyAssignment'
            ],
            'possibly-nullable-property-fetch' => [
                '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }
            
                    $a = rand(0, 10) ? new Foo() : null;
            
                    echo $a->foo;',
                'error_message' => 'PossiblyNullPropertyFetch'
            ],
            'nullable-property-fetch' => [
                '<?php
                    $a = null;
            
                    echo $a->foo;',
                'error_message' => 'NullPropertyFetch'
            ],
            'bad-array-property' => [
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
                'error_levels' => ['MixedAssignment']
            ],
            'not-set-in-empty-constructor' => [
                '<?php
                    class A {
                        /** @var int */
                        public $a;
        
                        public function __construct() { }
                    }',
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'no-constructor' => [
                '<?php
                    class A {
                        /** @var int */
                        public $a;
                    }',
                'error_message' => 'MissingConstructor'
            ],
            'not-set-in-all-branches-of-if' => [
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
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'property-set-in-protected-method' => [
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
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'defined-in-trait-not-set-in-empty-constructor' => [
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
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'property-set-in-private-method-with-if' => [
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
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'property-set-in-private-method-with-if-and-else' => [
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
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'undefined-property-class' => [
                '<?php
                    class A {
                        /** @var B */
                        public $foo;
                    }',
                'error_message' => 'UndefinedClass'
            ]
        ];
    }
}
