<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CloneTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     *
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'cloneCorrect' => [
                'code' => '<?php
                    class A {}
                    function foo(A $a) : A {
                        return clone $a;
                    }
                    $a = foo(new A());',
            ],
            'cloneCorrectWithPublicMethod' => [
                'code' => '<?php
                    class A {
                        public function __clone() {}
                    }
                    function foo(A $a) : A {
                        return clone $a;
                    }
                    foo(new A());',
            ],
            'clonePrivateInternally' => [
                'code' => '<?php
                    class A {
                        private function __clone() {}
                        public function foo(): self {
                            return clone $this;
                        }
                    }',
            ],
        ];
    }

    /**
     *
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidIntClone' => [
                'code' => '<?php
                    $a = 5;
                    clone $a;',
                'error_message' => 'InvalidClone',
            ],
            'possiblyInvalidIntClone' => [
                'code' => '<?php
                    $a = rand(0, 1) ? 5 : new Exception();
                    clone $a;',
                'error_message' => 'PossiblyInvalidClone',
            ],
            'invalidMixedClone' => [
                'code' => '<?php
                    /** @var mixed $a */
                    $a = 5;
                    clone $a;',
                'error_message' => 'MixedClone',
            ],
            'notVisibleCloneMethod' => [
                'code' => '<?php
                    class A {
                        private function __clone() {}
                    }
                    $a = new A();
                    clone $a;',
                'error_message' => 'InvalidClone',
            ],
            'notVisibleCloneMethodSubClass' => [
                'code' => '<?php
                    class a {
                        private function __clone() {}
                    }
                    class b extends a {}

                    clone new b;',
                'error_message' => 'InvalidClone',
            ],
            'notVisibleCloneMethodTrait' => [
                'code' => '<?php
                    trait a {
                        private function __clone() {}
                    }
                    class b {
                        use a;
                    }

                    clone new b;',
                'error_message' => 'InvalidClone',
            ],
            'invalidGenericClone' => [
                'code' => '<?php
                    /**
                     * @template T as int|string
                     * @param T $a
                     */
                    function foo($a): void {
                        clone $a;
                    }',
                'error_message' => 'InvalidClone',
            ],
            'possiblyInvalidGenericClone' => [
                'code' => '<?php
                    /**
                     * @template T as int|Exception
                     * @param T $a
                     */
                    function foo($a): void {
                        clone $a;
                    }',
                'error_message' => 'PossiblyInvalidClone',
            ],
            'mixedGenericClone' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $a
                     */
                    function foo($a): void {
                        clone $a;
                    }',
                'error_message' => 'MixedClone',
            ],
            'mixedTypeInferredIfErrors' => [
                'code' => '<?php
                    class A {}
                    /**
                     * @param A|string $a
                     */
                    function foo($a): void {
                        /**
                         * @psalm-suppress PossiblyInvalidClone
                         */
                        $cloned = clone $a;
                    }',
                'error_message' => 'MixedAssignment',
            ],
            'missingClass' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedDocblockClass
                     * @psalm-suppress InvalidReturnType
                     * @return Editable
                     */
                    function get() {}

                    /** @psalm-suppress UndefinedDocblockClass */
                    clone get();',
                'error_message' => 'InvalidClone',
            ],
        ];
    }
}
