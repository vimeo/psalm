<?php
namespace Psalm\Tests;

class ScopeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'new-var-in-if' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $badge = "hello";
                    }
                    else {
                        $badge = "goodbye";
                    }
            
                    echo $badge;'
            ],
            'new-var-in-if-with-else-return' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $badge = "hello";
                    }
                    else {
                        throw new \Exception();
                    }
            
                    echo $badge;'
            ],
            'try-catch-var' => [
                '<?php
                    try {
                        $worked = true;
                    }
                    catch (\Exception $e) {
                        $worked = false;
                    }',
                'assertions' => [
                    ['bool' => '$worked']
                ]
            ],
            'assignment-in-if' => [
                '<?php
                    if ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }'
            ],
            'negated-assignment-in-if' => [
                '<?php
                    if (!($row = (rand(0, 10) ? [5] : null))) {
                        // do nothing
                    }
                    else {
                        echo $row[0];
                    }'
            ],
            'assign-in-else-if' => [
                '<?php
                    if (rand(0, 10) > 5) {
                        echo "hello";
                    } elseif ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }'
            ],
            'if-not-equals-false' => [
                '<?php
                    if (($row = rand(0,10) ? [1] : false) !== false) {
                       echo $row[0];
                    }'
            ],
            'if-not-equals-null' => [
                '<?php
                    if (($row = rand(0,10) ? [1] : null) !== null) {
                       echo $row[0];
                    }'
            ],
            'if-null-not-equals' => [
                '<?php
                    if (null !== ($row = rand(0,10) ? [1] : null)) {
                       echo $row[0];
                    }'
            ],
            'if-null-equals' => [
                '<?php
                    if (null === ($row = rand(0,10) ? [1] : null)) {
            
                    } else {
                        echo $row[0];
                    }'
            ],
            'passed-by-ref-in-if' => [
                '<?php
                    if (preg_match("/bad/", "badger", $matches)) {
                        echo (string)$matches[0];
                    }'
            ],
            'pass-by-ref-in-if-check-after' => [
                '<?php
                    if (!preg_match("/bad/", "badger", $matches)) {
                        exit();
                    }
                    echo (string)$matches[0];'
            ],
            'pass-by-ref-in-if-with-boolean' => [
                '<?php
                    $a = true;
                    if ($a && preg_match("/bad/", "badger", $matches)) {
                        echo (string)$matches[0];
                    }'
            ],
            'pass-by-ref-in-var-with-boolean' => [
                '<?php
                    $a = preg_match("/bad/", "badger", $matches) > 0;
                    if ($a) {
                        echo (string)$matches[1];
                    }'
            ],
            'function-exists' => [
                '<?php
                    if (true && function_exists("flabble")) {
                        flabble();
                    }'
            ],
            'nested-property-fetch-in-elseif' => [
                '<?php
                    class A {
                        /** @var A|null */
                        public $foo;
            
                        public function __toString() : string {
                            return "boop";
                        }
                    }
            
                    $a = rand(0, 10) === 5 ? new A() : null;
            
                    if (false) {
            
                    }
                    elseif ($a && $a->foo) {
                        echo $a;
                    }'
            ],
            'global-return' => [
                '<?php
                    $foo = "foo";
            
                    function a() : string {
                        global $foo;
            
                        return $foo;
                    }'
            ],
            'negate-assertion-and-other' => [
                '<?php
                    $a = rand(0, 10) ? "hello" : null;
            
                    if (rand(0, 10) > 1 && is_string($a)) {
                        throw new \Exception("bad");
                    }',
                'assertions' => [
                    ['string|null' => '$a']
                ]
            ],
            'repeat-assertion-with-other' => [
                '<?php
                    $a = rand(0, 10) ? "hello" : null;
            
                    if (rand(0, 10) > 1 || is_string($a)) {
                        if (is_string($a)) {
                            echo strpos("e", $a);
                        }
                    }',
                'assertions' => [
                    ['string|null' => '$a']
                ]
            ],
            'refine-ored-type' => [
                '<?php
                    class A {
                        public function doThing() : void
                        {
                            if ($this instanceof B || $this instanceof C) {
                                if ($this instanceof B) {
            
                                }
                            }
                        }
                    }
                    class B extends A {}
                    class C extends A {}'
            ],
            'instance-of-subtraction' => [
                '<?php
                    class Foo {}
                    class FooBar extends Foo{}
                    class FooBarBat extends FooBar{}
                    class FooMoo extends Foo{}
            
                    $a = new Foo();
            
                    if ($a instanceof FooBar && !$a instanceof FooBarBat) {
            
                    } elseif ($a instanceof FooMoo) {
            
                    }'
            ],
            'static-null-ref' => [
                '<?php
                    /** @return void */
                    function foo() {
                        static $bar = null;
            
                        if ($bar !== null) {
                            // do something
                        }
            
                        $bar = 5;
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
            'possibly-undefined-var-in-if' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $b = "s";
                    }
            
                    echo $b;',
                'error_message' => 'PossiblyUndefinedVariable - somefile.php:6 - Possibly undefined variable $b, ' .
                    'first seen on line 3'
            ],
            'possibly-undefined-array-in-if' => [
                '<?php
                    if (rand(0,100) === 10) {
                        $array[] = "hello";
                    }
            
                    echo $array;',
                'error_message' => 'PossiblyUndefinedVariable - somefile.php:3 - Possibly undefined variable ' .
                    '$array, first seen on line 3'
            ],
            'invalid-global' => [
                '<?php
                    $a = "heli";
            
                    global $a;',
                'error_message' => 'InvalidGlobal'
            ],
            'this-in-static' => [
                '<?php
                    class A {
                        public static function fooFoo() {
                            echo $this;
                        }
                    }',
                'error_message' => 'InvalidStaticVariable'
            ],
            'static' => [
                '<?php
                    function a() : string {
                        static $foo = "foo";
            
                        return $foo;
                    }',
                'error_message' => 'MixedInferredReturnType'
            ]
        ];
    }
}
