<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class TryCatchTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>}>
     */
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
                    } catch (\Exception $e) {
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

                    echo $lastException->getMessage();'
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

                    echo $lastException->getMessage();'
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
                    }'
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
                    }'
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
                    }'
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
                    }'
            ],
            'suppressUndefinedVarInFinally' => [
                'code' => '<?php
                    try {} finally {
                        /** @psalm-suppress UndefinedGlobalVariable, MixedPropertyAssignment */
                        $event->end = null;
                    }
                ',
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
                    }'
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
                    }'
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
                    }'
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
                    }'
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
                'php_version' => '8.0'
            ],
            'issetInCatch' => [
                'code' => '<?php
                    function foo() : void {
                        try {
                            $a = 0;
                        } catch (Exception $e) {
                            echo isset($a) ? $a : 1;
                        }
                    }'
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
                    }'
            ],
            'unionAssignmentsFromTryAndCatch' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}

                    $foo = new A();
                    try {
                        $foo = new B();
                        $one = $foo;
                        $foo = new C();
                        $two = $foo;
                    } catch (Exception $_) {
                        $foo = new D();
                        $three = $foo;
                    }
                    $four = $foo;
                ',
                'assertions' => [
                    '$one?' => 'B',
                    '$two?' => 'C',
                    '$three?' => 'D',
                    '$four' => 'C|D',
                ],
            ],
            'unionAssignmentsFromTryAndMultipleCatch' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}
                    class E {}

                    $foo = new A();
                    try {
                        $foo = new B();
                        $one = $foo;
                        $foo = new C();
                        $two = $foo;
                    } catch (RuntimeException $_) {
                        $foo = new D();
                        $three = $foo;
                    } catch (Error $_) {
                        $foo = new E();
                        $four = $foo;
                    } catch (InvalidArgumentException $_) {
                        $five = $foo;
                    }
                    $six = $foo;
                ',
                'assertions' => [
                    '$one?' => 'B',
                    '$two?' => 'C',
                    '$three?' => 'D',
                    '$four?' => 'E',
                    '$five?' => 'A|B|C',
                    '$six' => 'A|B|C|D|E',
                ],
            ],
            'unionAssignmentsWithMultipleNestedTry' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                        $foo = 3;
                    } catch (Exception $_) {
                        $foo = 4;
                        try {
                            $foo = 5;
                            $foo = 6;
                        } catch (Exception $_) {
                            $foo = 7;
                        }
                        $one = $foo;
                    } catch (Error $_) {
                        try {
                            $foo = 8;
                            $foo = 9;
                        } catch (Exception $_) {
                            $foo = 10;
                            try {
                                $foo = 11;
                                $foo = 12;
                            } catch (Exception $_) {
                                $foo = 13;
                            } catch (Throwable $_) {
                            } finally {
                                $two = $foo;
                                $foo = 14;
                            }
                            $three = $foo;
                        }
                        $four = $foo;
                    } finally {
                        $five = $foo;
                        /** @psalm-check-type $five = 1|2|3|6|7|9|14 */;
                    }
                ',
                'assertions' => [
                    '$one?===' => '6|7',
                    '$two?===' => '10|11|12|13',
                    '$three?===' => '14',
                    '$four?===' => '9|14',
                    '$five===' => '3|6|7|9|14',
                ],
            ],
            'finallyOverridesTryAndCatch' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}
                    class E {}

                    $foo = new A();
                    try {
                        $foo = new B();
                        $one = $foo;
                        $foo = new C();
                        $two = $foo;
                    } catch (Exception $_) {
                        $foo = new D();
                        $three = $foo;
                    } finally {
                        $foo = new E();
                        $four = $foo;
                    }
                    $five = $foo;
                ',
                'assertions' => [
                    '$one?' => 'B',
                    '$two?' => 'C',
                    '$three?' => 'D',
                    '$four' => 'E',
                    '$five' => 'E',
                ],
            ],
            'unsetInTry' => [
                'code' => '<?php
                    $one = 1;
                    try {
                        $two = 2;
                        unset($one);
                    } catch (Exception $_) {
                    }
                ',
                'assertions' => [
                    '$one?===' => '1',
                    '$two?===' => '2',
                ],
            ],
            'unsetThenReassignedInTry' => [
                'code' => '<?php
                    $one = 1;
                    try {
                        $two = 2;
                        unset($one);
                        $one = 3;
                    } catch (Exception $_) {
                    }
                ',
                'assertions' => [
                    '$one?===' => '1|3',
                    '$two?===' => '2',
                ],
            ],
            'unsetThenReassignedInFinally' => [
                'code' => '<?php
                    $one = 1;
                    try {
                        $two = 2;
                        unset($one);
                    } catch (Exception $_) {
                    } finally {
                        $one = 3;
                    }
                ',
                'assertions' => [
                    '$one===' => '3',
                    '$two?===' => '2',
                ],
            ],
            'unsetInCatch' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                    } catch (Exception $_) {
                        unset($foo);
                    }
                ',
                'assertions' => [
                    '$foo?===' => '1|2',
                ],
            ],
            'unsetThenReassignedInCatch' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                    } catch (Exception $_) {
                        unset($foo);
                        $foo = 3;
                    }
                ',
                'assertions' => [
                    '$foo===' => '2|3',
                ],
            ],
            'unsetThenMaybeReassignedInCatch' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                    } catch (Exception $_) {
                        unset($foo);
                        try {
                            $foo = 3;
                        } catch (Exception $_) {
                        }
                    }
                ',
                'assertions' => [
                    '$foo?===' => '1|2|3',
                ],
            ],
            'unsetInNestedCatch' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                    } catch (Exception $_) {
                        try {
                            unset($foo);
                        } catch (Exception $_) {
                        }
                    }
                ',
                'assertions' => [
                    '$foo?===' => '1|2',
                ],
            ],
            'unsetInReturningCatch' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                    } catch (Exception $_) {
                        unset($foo);
                        return;
                    }
                ',
                'assertions' => [
                    '$foo===' => '1|2',
                ],
            ],
            'possiblyUndefinedBeforeTryIsStillPossiblyUndefined' => [
                'code' => '<?php
                    if (random_int(0, 1)) {
                        $maybeUnset = 1;
                    }

                    try {
                        $maybeUnset = 1;
                    } catch (Exception $_) {
                    }
                ',
                'assertions' => [
                    '$maybeUnset?===' => '1',
                ],
            ],
            'catchVarShadowsExistingVar' => [
                'code' => '<?php
                    $var = 1;
                    try {
                        $var = 2;
                        $one = $var;
                    } catch (Exception $var) {
                        $two = $var;
                    }
                    $three = $var;
                ',
                'assertions' => [
                    '$one?===' => '2',
                    '$two?' => 'Exception',
                    '$three===' => '2|Exception',
                ],
            ],
            'tryInCatchResolvesTypeCorrectly' => [
                'code' => '<?php
                    $var = 1;
                    try {
                        $var = 2;
                    } catch (Exception $_) {
                        try {
                            $var = 3;
                        } catch (Exception $_) {
                        }
                    }
                ',
                'assertions' => [
                    '$var===' => '1|2|3',
                ],
            ],
            'tryInFinallyResolvesTypeCorrectly' => [
                'code' => '<?php
                    $var = 1;
                    try {
                        $var = 2;
                    } catch (Exception $_) {
                    } finally {
                        try {
                            $var = 3;
                        } catch (Exception $_) {
                        }
                    }
                ',
                'assertions' => [
                    '$var===' => '1|2|3',
                ],
            ],
            'noCatchResolvesTypeCorrectly' => [
                'code' => '<?php
                    try {
                        $foo = 1;
                        $foo = 2;
                        $foo = 3;
                        $bar = 1;
                        $bar = 2;
                    } finally {
                        $bar = 3;
                    }
                ',
                'assertions' => [
                    '$foo===' => '3',
                    '$bar===' => '3',
                ],
            ],
            'universalCatchNarrowsTypeInFinally' => [
                'code' => '<?php
                    try {
                        $var = 1;
                        $var = 2;
                        $var = 3;
                    } catch (Exception $_) {
                        $var = 4;
                        $var = 5;
                    } catch (Throwable $_) {
                        $var = 6;
                        $var = 7;
                    } finally {
                        // Since an exception will always be caught and $var will be changed, $var can only be 3|5|7.
                        /** @psalm-check-type $var = 3|5|7 */;
                    }
                ',
                'assertions' => [
                    '$var===' => '3|5|7',
                ]
            ],
            'nonUniversalCatchDoesntNarrowTypeInFinally' => [
                'code' => '<?php
                    try {
                        $var = 1;
                        $var = 2;
                        $var = 3;
                    } catch (InvalidArgumentException $_) {
                        $var = 4;
                        $var = 5;
                    } catch (RuntimeException $_) {
                        $var = 6;
                        $var = 7;
                    } finally {
                        // $var could be 1 or 2 if SomeOtherException is thrown
                        /** @psalm-check-type $var? = 1|2|3|5|7 */;
                    }
                ',
                'assertions' => [
                    '$var===' => '3|5|7',
                ]
            ],
            'createByRefInIfInTry' => [
                'code' => '<?php
                    /**
                     * @param mixed $var
                     * @param-out int $var
                     */
                    function assignToInt(&$var): void
                    {
                        $var = 1;
                    }

                    try {
                        if (assignToInt($test)) {
                        }
                    } catch (Exception $_) {
                    }
                ',
                'assertions' => [
                    '$test?' => 'int',
                ],
            ],
            'extractInTry' => [
                'code' => '<?php
                    $foo = 1;
                    $bar = "baz";
                    try {
                        extract([]); // extract overrides all variables with `mixed`.
                    } catch (Exception $_) {
                    }
                ',
                'assertions' => [
                    '$foo' => 'int|mixed',
                    '$bar' => 'mixed|string',
                ],
            ],
            'returnInSomeCatches' => [
                'code' => '<?php
                    try {
                        $foo = 1;
                    } catch (RuntimeException $_) {
                        return;
                    } catch (InvalidArgumentException $_) {
                        $foo = 2;
                    } catch (Throwable $_) {
                        return;
                    }
                ',
                'assertions' => [
                    '$foo===' => '1|2',
                ],
            ],
            'tryVariableMightBeDefinedWhenTryLeavesScope' => [
                'code' => '<?php
                    try {
                        $foo = 1;
                        return;
                    } catch (Exception $_) {
                        $bar = 2;
                    }
                ',
                'assertions' => [
                    '$foo?===' => '1',
                    '$bar===' => '2',
                ],
            ],
            'variableOverriddenByAllCatchesWhenTryLeavesScope' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                        return;
                    } catch (InvalidArgumentException $_) {
                        $foo = 3;
                    } catch (RuntimeException $_) {
                        $foo = 4;
                    } finally {
                    }
                ',
                'assertions' => [
                    '$foo===' => '3|4',
                ],
            ],
            'variableOverriddenBySomeCatchesWhenTryLeavesScope' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                        return;
                    } catch (InvalidArgumentException $_) {
                        $foo = 3;
                    } catch (RuntimeException $_) {
                        $foo = 4;
                    } catch (LogicException $_) {
                    } finally {
                    }
                ',
                'assertions' => [
                    '$foo===' => '1|2|3|4',
                ],
            ],
            'variableOverriddenByCatchesThenOverriddenByFinallyWhenTryLeavesScope' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo = 2;
                        return;
                    } catch (InvalidArgumentException $_) {
                        $foo = 3;
                    } catch (RuntimeException $_) {
                        $foo = 4;
                    } finally {
                        $one = $foo;
                        /** @psalm-check-type $one = 1|2|3|4 */;
                        $foo = 5;
                    }
                    $two = $foo;
                ',
                'assertions' => [
                    '$one===' => '3|4',
                    '$two===' => '5',
                ],
            ],
            'variableModifiedInTryWithoutReassignment' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                        $foo += 1;
                        $foo += 1;
                    } finally {
                        /** @psalm-check-type $foo = 1|2|3 */;
                        $one = $foo;
                    }
                    $two = $foo;
                ',
                'assertions' => [
                    '$one===' => '3',
                    '$two===' => '3',
                ],
            ],
            'variableSetInNonLeavingCatchesButPossiblyChangedInFinally' => [
                'code' => '<?php
                    try {
                        $foo = 1;
                        $foo = 2;
                    } catch (InvalidArgumentException $_) {
                        $foo = 3;
                    } catch (RuntimeException $_) {
                        return;
                    } finally {
                        $one = $foo ?? 4;
                        /** @psalm-check-type $one = 1|2|3|4 */;
                        if (random_int(0, 1)) {
                            $foo = 5;
                        }
                    }
                ',
                'assertions' => [
                    '$one===' => '2|3|4',
                    '$foo===' => '2|3|5',
                ],
            ],
            'SKIPPED-finallyRunsEvenIfExceptionIsThrownInCatch' => [
                'code' => '<?php
                    try {
                        maybeThrow();
                        $foo = 1;
                        maybeThrow();
                        $foo = 2;
                        maybeThrow();
                    } catch (Exception $_) {
                        // Even if an exception is thrown at any point in this block, the `finally` still runs.
                        maybeThrow();
                        $foo = $foo ?? 10;
                        maybeThrow();
                        $foo += 1;
                        maybeThrow();
                        $foo += 1;
                        maybeThrow();
                        $foo += 1;
                        maybeThrow();
                    } finally {
                        /** @psalm-check-type $foo? = 1|2|3|4|5|10|11|12|13 */;
                    }

                    /** @throws Exception */
                    function maybeThrow(): void {}
                ',
                'assertions' => [
                    '$foo===' => '2|4|5|13',
                ],
            ],
            'SKIPPED-finallyRunsEvenIfExceptionIsThrownInCatchCorrectlyDetectsPossiblyUnset' => [
                'code' => '<?php
                    $foo = 1;
                    try {
                    } catch (Exception $_) {
                        unset($foo);
                        maybeThrow();
                        $foo = 2;
                    } finally {
                        /** @psalm-check-type $foo? = 1|2 */;
                    }

                    /** @throws Exception */
                    function maybeThrow(): void {}
                ',
                'assertions' => [
                    '$foo===' => '1|2',
                ],
            ],
            'tryInsideIfPossiblyUndefined' => [
                'code' => '<?php
                    function foo(): void
                    {
                        if (random_int(0, 1)) {
                            try {
                            } catch (Exception $e) {
                            }
                        }

                        if (isset($e)) {
                            throw $e;
                        }
                    }
                ',
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
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
                'error_message' => 'NullableReturnStatement'
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
                'error_message' => 'RedundantCondition'
            ],
            'assignmentInTryAndCatchDoesntGuaranteeAssignment' => [
                'code' => '<?php
                    interface SomeException extends Throwable {}

                    try {
                        $a = 1;
                    } catch (SomeException $_) {
                        $a = 2;
                    } catch (Throwable $_) {
                    }

                    // What if SomeOtherException was thrown?
                    echo $a;
                ',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'redundantCatch' => [
                'code' => '<?php
                    try {
                    } catch (Throwable $_) {
                    } catch (Exception $_) {
                    }
                ',
                'error_message' => 'RedundantCatch',
            ],
            'redundantCatchSameStatement' => [
                'code' => '<?php
                    try {
                    } catch (Throwable|Exception $_) {
                    }
                ',
                'error_message' => 'RedundantCatch',
            ],
            'redundantCatchSameStatementChildFirst' => [
                'code' => '<?php
                    try {
                    } catch (Exception|Throwable $_) {
                    }
                ',
                'error_message' => 'RedundantCatch',
            ],
            'missingCatchVariablePhp7' => [
                'code' => '<?php
                    try {
                    } catch (Throwable) {
                    }
                ',
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'unsetInFinally' => [
                'code' => '<?php
                    $one = 1;
                    try {
                    } catch (Exception $_) {
                    } finally {
                        unset($one);
                    }
                    echo $one;
                ',
                'error_message' => 'UndefinedGlobalVariable',
            ],
        ];
    }
}
