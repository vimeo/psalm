<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class AnnotationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return void
     */
    public function testNopType()
    {
        $stmts = self::$parser->parse('<?php
        $a = "hello";

        /** @var int $a */
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'deprecatedMethod' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar() : void {
                        }
                    }'
            ],
            'validDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo() : string {
                        return "boop";
                    }
            
                    /**
                     * @return array<int, string>
                     */
                    function foo2() : array {
                        return ["hello"];
                    }
            
                    /**
                     * @return array<int, string>
                     */
                    function foo3() : array {
                        return ["hello"];
                    }'
            ],
            'reassertWithIs' => [
                '<?php
                    /** @param array $a */
                    function foo($a) : void {
                        if (is_array($a)) {
                            // do something
                        }
                    }'
            ],
            'checkArrayWithIs' => [
                '<?php
                    /** @param mixed $b */
                    function foo($b) : void {
                        /** @var array */
                        $a = (array)$b;
                        if (is_array($a)) {
                            // do something
                        }
                    }'
            ],
            'checkArrayWithIsInsideLoop' => [
                '<?php
                    /** @param array<mixed, array<mixed, mixed>> $data */
                    function foo($data) : void {
                        foreach ($data as $key => $val) {
                            if (!\is_array($data)) {
                                $data = [$key => null];
                            } else {
                                $data[$key] = !empty($val);
                            }
                        }
                    }'
            ],
            'goodDocblock' => [
                '<?php
                    class A {
                        /**
                         * @param A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b) : void {
                        }
                    }'
            ],
            'goodDocblockInNamespace' => [
                '<?php
                    namespace Foo;
            
                    class A {
                        /**
                         * @param \Foo\A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b) : void {
                        }
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
            'deprecatedMethodWithCall' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar() : void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedMethod'
            ],
            'invalidDocblockParam' => [
                '<?php
                    /**
                     * @param int $bar
                     */
                    function fooFoo(array $bar) : void {
                    }',
                'error_message' => 'InvalidDocblock'
            ],
            'extraneousDocblockParam' => [
                '<?php
                    /**
                     * @param int $bar
                     */
                    function fooBar() : void {
                    }',
                'error_message' => 'InvalidDocblock - somefile.php:3 - Parameter $bar does not appear in the ' .
                    'argument list for fooBar'
            ],
            'missingParamType' => [
                '<?php
                    /**
                     * @param $bar
                     */
                    function fooBar() : void {
                    }',
                'error_message' => 'InvalidDocblock - somefile.php:3 - Parameter $bar does not appear in the ' .
                    'argument list for fooBar'
            ],
            'missingParamVar' => [
                '<?php
                    /**
                     * @param string
                     */
                    function fooBar() : void {
                    }',
                'error_message' => 'InvalidDocblock - somefile.php:5 - Badly-formatted @param in docblock for fooBar'
            ],
            'invalidDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo() : void {
                    }',
                'error_message' => 'InvalidDocblock'
            ]
        ];
    }
}
