<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class TryCatchTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'addThrowableInterfaceType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $foo = true;

                    try {
                      $a->bar();
                    } catch (\TypeError $e) {
                      $foo = false;
                    }

                    if (!$foo) {}',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedGlobalVariable',
                    'MixedMethodCall',
                ],
            ],
            'issetAfterTryCatchWithoutAssignmentInCatch' => [
                'code' => '<?php
                    function test(): string {
                        throw new Exception("bad");
                    }

                    $a = "foo";

                    try {
                        $var = test();
                    } catch (Exception $e) {
                        echo "bad";
                    }

                    if (isset($var)) {}

                    echo $a;',
            ],
            'issetAfterTryCatchWithoutAssignmentInCatchButReturn' => [
                'code' => '<?php
                    function test(): string {
                        throw new Exception("bad");
                    }

                    $a = "foo";

                    try {
                        $var = test();
                    } catch (Exception $e) {
                        return;
                    }

                    echo $var;

                    echo $a;',
            ],
            'issetAfterTryCatchWithAssignmentInCatch' => [
                'code' => '<?php
                    function test(): string {
                        throw new Exception("bad");
                    }

                    $a = "foo";

                    try {
                        $var = test();
                    } catch (Exception $e) {
                        $var = "bad";
                    }

                    echo $var;
                    echo $a;',
            ],
            'issetAfterTryCatchWithIfInCatch' => [
                'code' => '<?php
                    function test(): string {
                        throw new Exception("bad");
                    }

                    function foo() : void {
                        $a = null;

                        $params = null;

                        try {
                            $a = test();

                            $params = $a;
                        } catch (\Exception $exception) {
                            $params = "hello";
                        }

                        echo $params;
                    }',
            ],
            'noRedundantConditionsInFinally' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'noReturnInsideCatch' => [
                'code' => '<?php
                    /**
                     * @return never-returns
                     */
                    function example() : void {
                        throw new Exception();
                    }

                    try {
                        $str = "a";
                    } catch (Exception $e) {
                        example();
                    }
                    ord($str);',
            ],
            'varSetInOnlyCatch' => [
                'code' => '<?php
                    try {
                        if (rand(0, 1)) {
                            throw new \Exception("Gotcha!");
                        }

                        exit;
                    } catch (\Exception $e) {
                        $lastException = $e;
                    }

                    echo $lastException->getMessage();',
            ],
            'varSetInOnlyCatchWithNull' => [
                'code' => '<?php
                    $lastException = null;

                    try {
                        if (rand(0, 1)) {
                            throw new \Exception("Gotcha!");
                        }

                        exit;
                    } catch (\Exception $e) {
                        $lastException = $e;
                    }

                    echo $lastException->getMessage();',
            ],
            'allowDoubleNestedLoop' => [
                'code' => '<?php
                    function foo() : void {
                        do {
                            try {
                                do {
                                    $count = rand(0, 10);
                                } while ($count === 5);
                            } catch (Exception $e) {}
                        } while (rand(0, 1));
                    }',
            ],
            'aliasException' => [
                'code' => '<?php
                    namespace UserException;

                    class UserException extends \Exception {

                    }

                    namespace Alias\UserException;

                    use function class_alias;

                    class_alias(
                        \UserException\UserException::class,
                        \Alias\UserException\UserExceptionAlias::class
                    );

                    namespace Client;

                    try {
                        throw new \Alias\UserException\UserExceptionAlias();
                    } catch (\Alias\UserException\UserExceptionAlias $e) {
                        // do nothing
                    }',
            ],
            'aliasAnotherException' => [
                'code' => '<?php
                    namespace UserException;

                    class UserException extends \Exception {

                    }

                    namespace Alias\UserException;

                    use function class_alias;

                    class_alias(
                        \UserException\UserException::class,
                        \'\Alias\UserException\UserExceptionAlias\'
                    );

                    namespace Client;

                    try {
                        throw new \Alias\UserException\UserExceptionAlias();
                    } catch (\Alias\UserException\UserExceptionAlias $e) {
                        // do nothing
                    }',
            ],
            'notRedundantVarCheckInFinally' => [
                'code' => '<?php
                    $var = "a";
                    try {
                        if (rand(0, 1)) {
                            throw new \Exception();
                        }
                        $var = "b";
                    } finally {
                        if ($var === "a") {
                            echo $var;
                        }
                    }',
            ],
            'suppressUndefinedVarInFinally' => [
                'code' => '<?php
                    try {} finally {
                        /** @psalm-suppress UndefinedGlobalVariable, MixedPropertyAssignment */
                        $event->end = null;
                    }',
            ],
            'returnsInTry' => [
                'code' => '<?php
                    final class A
                    {
                        private ?string $property = null;

                        public function handle(string $arg): string
                        {
                            if (null !== $this->property) {
                                return $arg;
                            }

                            try {
                                return $arg;
                            } finally {
                            }
                        }
                    }',
            ],
            'finallyArgMaybeUndefined' => [
                'code' => '<?php
                    class TestMe {
                        private function startTransaction(): void {
                        }

                        private function endTransaction(bool $commit): void {
                            echo $commit ? "Committing" : "Rolling back";
                        }

                        public function doWork(): void {
                            $this->startTransaction();
                            try {
                                $this->workThatMayOrMayNotThrow();
                                $success = true;
                            } finally {
                                $this->endTransaction($success ?? false);
                            }
                        }

                        private function workThatMayOrMayNotThrow(): void {}
                    }',
            ],
            'finallyArgIsNotUndefinedIfSet' => [
                'code' => '<?php
                    function fooFunction (): string {
                        try{
                            $foo = "foo";
                        } finally {
                            /** @psalm-suppress PossiblyUndefinedVariable */
                            echo $foo;
                            $foo = "bar";
                        }

                        return $foo;
                    }',
            ],
            'allowReturningPossiblyUndefinedFromTry' => [
                'code' => '<?php
                    function fooFunction (): string {
                        try{
                            $foo = "foo";
                        } finally {
                            /** @psalm-suppress PossiblyUndefinedVariable */
                            echo $foo;
                        }

                        return $foo;
                    }',
            ],
            'mixedNotUndefinedAfterTry' => [
                'code' => '<?php
                    /**
                     * @return array<int, mixed>
                     * @psalm-suppress MixedAssignment
                     */
                    function fetchFromCache(mixed $m)
                    {
                        $data = [];

                        try {
                            $value = $m;
                        } catch (Throwable $e) {
                            $value = $m;
                        }

                        $data[] = $value;

                        return $data;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'issetInCatch' => [
                'code' => '<?php
                    function foo() : void {
                        try {
                            $a = 0;
                        } catch (Exception $e) {
                            echo isset($a) ? $a : 1;
                        }
                    }',
            ],
            'issetExceptionInFinally' => [
                'code' => '<?php
                    try {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }
                    } catch (Throwable $exception) {
                        //throw $exception;
                    } finally {
                        if (isset($exception)) {}
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidCatchClass' => [
                'code' => '<?php
                    class A {}
                    try {
                        $worked = true;
                    }
                    catch (A $e) {}',
                'error_message' => 'InvalidCatch',
            ],
            'invalidThrowClass' => [
                'code' => '<?php
                    class A {}
                    throw new A();',
                'error_message' => 'InvalidThrow',
            ],
            'theresNoCatch' => [
                'code' => '<?php
                    function missing_return() : bool {
                        try {
                        } finally {
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'catchDoesNotReturn' => [
                'code' => '<?php
                    function missing_return() : bool {
                        try {
                        } finally {
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'catchWithNoReturnAndFinallyDoesNotReturn' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'preventPossiblyUndefinedVarInTry' => [
                'code' => '<?php
                    class Foo {
                        public static function possiblyThrows(): bool {
                            $result = (bool)rand(0, 1);

                            if (!$result) {
                                throw new \Exception("BOOM");
                            }

                            return true;
                        }
                    }

                    try {
                        $result = Foo::possiblyThrows();
                        $a = "ACME";

                        if ($result) {
                            echo $a;
                        }
                    } catch (\Exception $e) {
                        echo $a;
                    }',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'possiblyNullReturnInTry' => [
                'code' => '<?php
                    function foo() : string {
                        $a = null;

                        try {
                            $a = dangerous();
                        } catch (Exception $e) {
                            return $a;
                        }

                        return $a;
                    }

                    function dangerous() : string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }
                        return "hello";
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'isAlwaysDefinedInFinally' => [
                'code' => '<?php
                    function maybeThrows() : void {
                        if (rand(0, 1)) {
                            throw new UnexpectedValueException();
                        }
                    }

                    function doTry() : void {
                        $exception = new \Exception();

                        try {
                            maybeThrows();
                            return;
                        } catch (Exception $exception) {
                            throw $exception;
                        } finally {
                            if ($exception) {
                                echo "here";
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
