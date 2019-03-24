<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class TryCatchTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'addThrowableInterfaceType' => [
                '<?php
                    interface CustomThrowable {}
                    class CustomException extends Exception implements CustomThrowable {}

                    /** @psalm-suppress InvalidCatch */
                    try {
                        throw new CustomException("Bad");
                    } catch (CustomThrowable $e) {
                        echo $e->getMessage();
                    }',
            ],
            'rethrowInterfaceExceptionWithoutInvalidThrow' => [
                '<?php
                    interface CustomThrowable {}
                    class CustomException extends Exception implements CustomThrowable {}

                    /** @psalm-suppress InvalidCatch */
                    try {
                        throw new CustomException("Bad");
                    } catch (CustomThrowable $e) {
                        throw $e;
                    }',
            ],
            'tryCatchVar' => [
                '<?php
                    try {
                        $worked = true;
                    }
                    catch (\Exception $e) {
                        $worked = false;
                    }',
                'assertions' => [
                    '$worked' => 'bool',
                ],
            ],
            'alwaysReturnsBecauseCatchDoesNothing' => [
                '<?php
                    function throws(): void {
                        throw new Exception("bad");
                    }
                    function foo(): string {
                        try {
                            throws();
                        } catch (Exception $e) {
                            // do nothing
                        }

                        return "hello";
                    }',
            ],
            'wheresTheCatch' => [
                '<?php
                    function foo() : bool {
                        try {
                            return true;
                        } finally {
                        }
                    }

                    function bar() : bool {
                        try {
                            // do nothing
                        } finally {
                            return true;
                        }
                    }',
            ],
            'catchWithNoReturnButFinallyReturns' => [
                '<?php
                    function foo() : bool {
                        try {
                            if (rand(0, 1)) throw new Exception("bad");
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            // do nothing here either
                        } finally {
                            return true;
                        }
                    }',
            ],
            'stopAnalysisAfterBadTryIssue' => [
                '<?php
                    $foo = true;

                    try {
                      $a->bar();
                    } catch (\TypeError $e) {
                      $foo = false;
                    }

                    if (!$foo) {}',
                'assertions' => [],
                'error_message' => [
                    'UndefinedGlobalVariable' => \Psalm\Config::REPORT_INFO,
                    'MixedMethodCall' => \Psalm\Config::REPORT_INFO,
                ],
            ],
            'issetAfterTryCatch' => [
                '<?php
                    function test(): string {
                        throw new Exception("bad");
                    }

                    try {
                        $a = "foo";
                        $var = test();
                    } catch (Exception $e) {
                        echo "bad";
                    }

                    if (isset($var)) {}

                    echo $a;',
            ],
            'issetAfterTryCatchWithCombinedType' => [
                '<?php
                    function test(): string {
                        throw new Exception("bad");
                    }

                    try {
                        $a = "foo";
                        $var = test();
                    } catch (Exception $e) {
                        $var = "bad";
                    }

                    if (isset($var)) {}

                    echo $a;',
            ],
            'noRedundantConditionsInFinally' => [
                '<?php
                    function doThings(): void {}
                    function message(): string { return "message"; }

                    $errors = [];

                    try {
                        doThings();
                    } catch (RuntimeException $e) {
                        $errors["field"] = message();
                    } catch (LengthException $e) {
                        $errors[rand(0,1) ? "field" : "field2"] = message();
                    } finally {
                        if (!empty($errors)) {
                            return $errors;
                        }
                    }',
            ],
            'typeDoesNotContainTypeInCatch' => [
                '<?php
                    function foo(bool $test, callable $bar): string {
                        try {
                            $bar();

                            if ($test) {
                                return "moo";
                            }
                            return "miau";
                        } catch (\Exception $exception) {
                            if ($test) {
                                return "moo";
                            }
                            return "miau";
                        }
                    }',
            ],
            'notAlwaysUndefinedVarInFinally' => [
                '<?php
                    function maybeThrows() : void {
                        if (rand(0, 1)) {
                            throw new UnexpectedValueException();
                        }
                    }

                    function doTry() : void {
                        try {
                            maybeThrows();
                            return;
                        } catch (Exception $exception) {
                            throw $exception;
                        } finally {
                            if (isset($exception)) {
                                echo "here";
                            }
                        }
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidCatchClass' => [
                '<?php
                    class A {}
                    try {
                        $worked = true;
                    }
                    catch (A $e) {}',
                'error_message' => 'InvalidCatch',
            ],
            'invalidThrowClass' => [
                '<?php
                    class A {}
                    throw new A();',
                'error_message' => 'InvalidThrow',
            ],
            'theresNoCatch' => [
                '<?php
                    function missing_return() : bool {
                        try {
                        } finally {
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'catchDoesNotReturn' => [
                '<?php
                    function missing_return() : bool {
                        try {
                        } finally {
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'catchWithNoReturnAndFinallyDoesNotReturn' => [
                '<?php
                    function foo() : bool {
                        try {
                            if (rand(0, 1)) throw new Exception("bad");
                            return true;
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            // do nothing here either
                        } finally {

                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'catchWithNoReturnAndNoFinally' => [
                '<?php
                    function foo() : bool {
                        try {
                            if (rand(0, 1)) throw new Exception("bad");
                            return true;
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            // do nothing here either
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
        ];
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UncaughtThrowInGlobalScope
     *
     * @return                   void
     */
    public function testUncaughtThrowInGlobalScope()
    {
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            'somefile.php',
            '<?php
                throw new \Exception();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return                   void
     */
    public function testCaughtThrowInGlobalScope()
    {
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            'somefile.php',
            '<?php
                try {
                    throw new \Exception();
                } catch (\Exception $e) {}'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }
}
