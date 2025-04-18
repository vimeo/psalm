<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class PureAnnotationAdditionTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'addPureAnnotationToFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                'input' => '<?php
                    /**
                     * @return string
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                'output' => '<?php
                    /**
                     * @return string
                     *
                     * @psalm-pure
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToImpureFunction' => [
                'input' => '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToMutationFreeMethod' => [
                'input' => '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureCall' => [
                'input' => '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                'output' => '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureClosure' => [
                'input' => '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                'output' => '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddWhenReferencingThis' => [
                'input' => '<?php
                    abstract class A {
                        public int $a = 5;

                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                'output' => '<?php
                    abstract class A {
                        public int $a = 5;

                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddInChildMethod' => [
                'input' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'doAddInOtherMethod' => [
                'input' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureIfCallableNotPure' => [
                'input' => '<?php
                    function pure(callable $callable): string{
                        return $callable();
                    }',
                'output' => '<?php
                    function pure(callable $callable): string{
                        return $callable();
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPureAnnotation'],
                'safe_types' => true,
            ],
        ];
    }
}
