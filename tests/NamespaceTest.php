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
            'sameNameImportedInDifferentNamespaces' => [
                'code' => '<?php
                    namespace A {
                        use Exception;
                        use function strlen;
                        use const PHP_EOL;

                        echo strlen("a") . PHP_EOL;
                        throw new Exception("e");
                    }

                    namespace B {
                        use Exception;
                        use function strlen;
                        use const PHP_EOL;

                        echo strlen("a") . PHP_EOL;
                        throw new Exception("e");
                    }',
            ],
            'sameNameImportedAsClassFunctionAndConstant' => [
                'code' => '<?php
                    namespace Test;

                    use Exception;
                    use function Exception;
                    use const Exception;',
                'assertions' => [],
                'ignored_issues' => ['UnusedFunctionCall', 'UndefinedFunction', 'UndefinedConstant'],
            ],
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
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'classImportedTwice' => [
                'code' => '<?php
                    namespace Test;

                    use Exception;
                    use Exception;

                    throw new Exception("e");',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:25 - Cannot use Exception as Exception because the name is already in use',
            ],
            'aliasUsedForTwoClasses' => [
                'code' => '<?php
                    namespace Test;

                    use Foo\Bar as Baz;
                    use Other\Baz;',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:25 - Cannot use Other\Baz as Baz because the name is already in use',
            ],
            'functionImportedTwice' => [
                'code' => '<?php
                    use function strlen;
                    use function strlen;',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:34 - Cannot use strlen as strlen because the name is already in use',
            ],
            'constantImportedTwice' => [
                'code' => '<?php
                    use const PHP_EOL;
                    use const PHP_EOL;',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:31 - Cannot use PHP_EOL as PHP_EOL because the name is already in use',
            ],
            'groupUseImportedTwice' => [
                'code' => '<?php
                    namespace Test;

                    use Foo\{A, B};
                    use Foo\{A};',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:30 - Cannot use A as A because the name is already in use',
            ],
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
        ];
    }
}
