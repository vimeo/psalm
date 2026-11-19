<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class NamespaceTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'emptyNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @return void */
                        function foo() {

                        }

                        class Bar {

                        }
                    }
                    namespace {
                        A\foo();
                        \A\foo();

                        (new A\Bar);
                    }',
            ],
            'constantReference' => [
                'code' => '<?php
                    namespace Aye\Bee {
                        const HELLO = "hello";
                    }
                    namespace Aye\Bee {
                        /** @return void */
                        function foo() {
                            echo \Aye\Bee\HELLO;
                        }

                        class Bar {
                            /** @return void */
                            public function foo() {
                                echo \Aye\Bee\HELLO;
                            }
                        }
                    }',
            ],
            'argvReference' => [
                'code' => '<?php
                    namespace Foo;

                    $a = $argv;
                    $b = $argc;',
            ],
            'argvReferenceInFunction' => [
                'code' => '<?php
                    namespace Foo;

                    function foo() : void {
                        global $argv;

                        $c = $argv;
                    }',
            ],
            'varsAreNotScoped' => [
                'code' => '<?php
                    namespace A {
                        $a = "1";
                    }
                    namespace B\C {
                        $bc = "2";
                    }
                    namespace {
                        echo $a . PHP_EOL;
                        echo $bc . PHP_EOL;
                    }
                ',
                'assertions' => [
                    '$a===' => "'1'",
                    '$bc===' => "'2'",
                ],
            ],
            'sameUseInSeparateNamespaceBlocks' => [
                'code' => '<?php
                    namespace A;

                    use B\Foo;
                    use function B\foo;
                    use const B\FOO;
                    use const B\foo;

                    namespace A;

                    use B\Foo;
                    use function B\foo;
                    use const B\FOO;',
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
            'callNamespacedFunctionFromEmptyNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @return void */
                        function foo() {

                        }
                    }
                    namespace {
                        foo();
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'callRootFunctionFromNamespace' => [
                'code' => '<?php
                    namespace {
                        /** @return void */
                        function foo() {

                        }
                    }
                    namespace A {
                        \A\foo();
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'duplicateUseInNamespaceBlock' => [
                'code' => '<?php
                    namespace X;

                    use \stdClass;

                    namespace X;

                    use \stdClass;
                    use \stdClass;',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:25 - '
                    . 'Cannot use stdClass as stdClass because the name is already in use',
            ],
            'duplicateUseAliasIsCaseInsensitive' => [
                'code' => '<?php
                    namespace X;

                    use A\Foo;
                    use B\foo;',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:25 - '
                    . 'Cannot use B\foo as foo because the name is already in use',
            ],
            'duplicateFunctionUseInGroupUse' => [
                'code' => '<?php
                    namespace X;

                    use function A\foo;
                    use function B\{foo};',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:37 - '
                    . 'Cannot use function B\foo as foo because the name is already in use',
            ],
            'duplicateConstUseInBracedNamespace' => [
                'code' => '<?php
                    namespace X {
                        use const A\FOO;
                        use const B\FOO as FOO;
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:35 - '
                    . 'Cannot use const B\FOO as FOO because the name is already in use',
            ],
        ];
    }
}
