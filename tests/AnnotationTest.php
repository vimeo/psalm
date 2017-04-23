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
            'deprecated-method' => [
                '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar() : void {
                        }
                    }'
            ],
            'valid-docblock-return' => [
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
            'reassert-with-is' => [
                '<?php
                    /** @param array $a */
                    function foo($a) : void {
                        if (is_array($a)) {
                            // do something
                        }
                    }'
            ],
            'check-array-with-is' => [
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
            'check-array-with-is-inside-loop' => [
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
            'good-docblock' => [
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
            'good-docblock-in-namespace' => [
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
            'deprecated-method-with-call' => [
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
            'invalid-docblock-param' => [
                '<?php
                    /**
                     * @param int $bar
                     */
                    function fooFoo(array $bar) : void {
                    }',
                'error_message' => 'InvalidDocblock'
            ],
            'extraneous-docblock-param' => [
                '<?php
                    /**
                     * @param int $bar
                     */
                    function fooBar() : void {
                    }',
                'error_message' => 'InvalidDocblock - somefile.php:3 - Parameter $bar does not appear in the ' .
                    'argument list for fooBar'
            ],
            'missing-param-type' => [
                '<?php
                    /**
                     * @param $bar
                     */
                    function fooBar() : void {
                    }',
                'error_message' => 'InvalidDocblock - somefile.php:3 - Parameter $bar does not appear in the ' .
                    'argument list for fooBar'
            ],
            'missing-param-var' => [
                '<?php
                    /**
                     * @param string
                     */
                    function fooBar() : void {
                    }',
                'error_message' => 'InvalidDocblock - somefile.php:5 - Badly-formatted @param in docblock for fooBar'
            ],
            'invalid-docblock-return' => [
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
