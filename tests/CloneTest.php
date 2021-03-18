<?php

declare(strict_types=1);

namespace Psalm\Tests;

class CloneTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'cloneCorrect' => [
                '<?php
                    class A {}
                    function foo(A $a) : A {
                        return clone $a;
                    }
                    $a = foo(new A());',
            ],
            'cloneCorrectWithPublicMethod' => [
                '<?php
                    class A {
                        public function __clone() {}
                    }
                    function foo(A $a) : A {
                        return clone $a;
                    }
                    foo(new A());',
            ],
            'clonePrivateInternally' => [
                '<?php
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
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidIntClone' => [
                '<?php
                    $a = 5;
                    clone $a;',
                'error_message' => 'InvalidClone',
            ],
            'possiblyInvalidIntClone' => [
                '<?php
                    $a = rand(0, 1) ? 5 : new Exception();
                    clone $a;',
                'error_message' => 'PossiblyInvalidClone',
            ],
            'invalidMixedClone' => [
                '<?php
                    /** @var mixed $a */
                    $a = 5;
                    clone $a;',
                'error_message' => 'MixedClone',
            ],
            'notVisibleCloneMethod' => [
                '<?php
                    class A {
                        private function __clone() {}
                    }
                    $a = new A();
                    clone $a;',
                'error_message' => 'InvalidClone',
            ],
            'invalidGenericClone' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
