<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class ImmutableAnnotationAdditionTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'addPureAnnotationToFunction' => [
                'input' => '<?php
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'output' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                'input' => '<?php
                    /**
                     * This is a class
                     * that is cool
                     *
                     * @Foo\Bar
                     */
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'output' => '<?php
                    /**
                     * This is a class
                     * that is cool
                     *
                     * @Foo\Bar
                     *
                     * @psalm-immutable
                     */
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationWhenMethodHasImpurity' => [
                'input' => '<?php
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            echo $this->i;
                            return $this->i + 5;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            echo $this->i;
                            return $this->i + 5;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationWhenClassCanHoldMutableData' => [
                'input' => '<?php
                    class B {
                        public int $i = 5;
                    }

                    class A {
                        public B $b;

                        public function __construct(B $b) {
                            $this->b = $b;
                        }

                        public function getPlus5() {
                            return $this->b->i + 5;
                        }
                    }

                    $b = new B();

                    $a = new A($b);

                    echo $a->getPlus5();

                    $b->i = 6;

                    echo $a->getPlus5();',
                'output' => '<?php
                    class B {
                        public int $i = 5;
                    }

                    /**
                     * @psalm-immutable
                     */
                    class A {
                        public B $b;

                        public function __construct(B $b) {
                            $this->b = $b;
                        }

                        public function getPlus5() {
                            return $this->b->i + 5;
                        }
                    }

                    $b = new B();

                    $a = new A($b);

                    echo $a->getPlus5();

                    $b->i = 6;

                    echo $a->getPlus5();',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToClassThatExtends' => [
                'input' => '<?php
                    class AParent {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function mutate() : void {
                            echo "hello";
                        }
                    }

                    class A extends AParent {
                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'output' => '<?php
                    class AParent {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function mutate() : void {
                            echo "hello";
                        }
                    }

                    class A extends AParent {
                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
        ];
    }
}
