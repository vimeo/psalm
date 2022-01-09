<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PropertiesOfTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'varStatement' => [
                '<?php
                class A {
                    public int $foo = 42;
                }

                /** @var properties-of<A> */
                $test = \'foo\';
                ',
            ],
            'returnStatement' => [
                '<?php
                class A {
                    public int $foo = 42;
                }

                /** @return properties-of<A> */
                function returnPropertyOfA() {
                    return \'foo\';
                }
                ',
            ],
            'publicPropertiesOf' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return public-properties-of<A> */
                function returnPropertyOfA() {
                    return \'foo\';
                }
                ',
            ],
            'protectedPropertiesOf' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return protected-properties-of<A> */
                function returnPropertyOfA() {
                    return \'adams\';
                }
                ',
            ],
            'privatePropertiesOf' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return private-properties-of<A> */
                function returnPropertyOfA() {
                    return \'bar\';
                }
                ',
            ],
            'allPropertiesOf' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return properties-of<A> */
                function returnPropertyOfA(int $visibility) {
                    if ($visibility === 1) {
                        return \'foo\';
                    } elseif ($visibility === 2) {
                        return \'bar\';
                    } else {
                        return \'adams\';
                    }
                }
                ',
            ],
            'usePropertiesOfSelfAsArrayKey' => [
                '<?php
                class A {
                    /** @var int */
                    public $a = 1;
                    /** @var int */
                    public $b = 2;

                    /** @return array<properties-of<self>, int> */
                    public function asArray() {
                        return [
                            \'a\' => $this->a,
                            \'b\' => $this->b
                        ];
                    }
                }',
            ],
            'usePropertiesOfStaticAsArrayKey' => [
                '<?php
                class A {
                    /** @var int */
                    public $a = 1;
                    /** @var int */
                    public $b = 2;

                    /** @return array<properties-of<static>, int> */
                    public function asArray() {
                        return [
                            \'a\' => $this->a,
                            \'b\' => $this->b
                        ];
                    }
                }

                class B extends A {
                    /** @var int */
                    public $c = 3;

                    public function asArray() {
                        return [
                            \'a\' => $this->a,
                            \'b\' => $this->b,
                            \'c\' => $this->c,
                        ];
                    }
                }
                ',
            ],
            'propertiesOfMultipleInheritanceStaticAsArrayKey' => [
                '<?php
                class A {
                    /** @var int */
                    public $a = 1;
                    /** @var int */
                    public $b = 2;

                    /** @return array<properties-of<static>, int> */
                    public function asArray() {
                        return [
                            \'a\' => $this->a,
                            \'b\' => $this->b
                        ];
                    }
                }

                class B extends A {
                    /** @var int */
                    public $c = 3;
                }

                class C extends B {
                    /** @var int */
                    public $d = 4;

                    public function asArray() {
                        return [
                            \'a\' => $this->a,
                            \'b\' => $this->b,
                            \'c\' => $this->c,
                            \'d\' => $this->d,
                        ];
                    }
                }
                ',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'onlyOneTemplateParam' => [
                '<?php

                class A {}
                class B {}

                /** @var properties-of<A, B> */
                $test = \'foobar\';
                ',
                'error_message' => 'InvalidDocblock',
            ],
            'publicPropertiesOfPicksNoPrivate' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return public-properties-of<A> */
                function returnPropertyOfA() {
                    return \'bar\';
                }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'publicPropertiesOfPicksNoProtected' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return public-properties-of<A> */
                function returnPropertyOfA() {
                    return \'adams\';
                }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'protectedPropertiesOfPicksNoPublic' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return protected-properties-of<A> */
                function returnPropertyOfA() {
                    return \'foo\';
                }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'protectedPropertiesOfPicksNoPrivate' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return protected-properties-of<A> */
                function returnPropertyOfA() {
                    return \'bar\';
                }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'privatePropertiesOfPicksNoPublic' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return private-properties-of<A> */
                function returnPropertyOfA() {
                    return \'foo\';
                }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
            'privatePropertiesOfPicksNoProtected' => [
                '<?php
                class A {
                    /** @var mixed */
                    public $foo;
                    /** @var mixed */
                    private $bar;
                    /** @var mixed */
                    protected $adams;
                }

                /** @return private-properties-of<A> */
                function returnPropertyOfA() {
                    return \'adams\';
                }
                ',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}
