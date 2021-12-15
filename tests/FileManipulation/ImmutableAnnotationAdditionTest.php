<?php

namespace Psalm\Tests\FileManipulation;

class ImmutableAnnotationAdditionTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'addPureAnnotationToFunction' => [
                '<?php
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                '<?php
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
                '7.4',
                ['MissingImmutableAnnotation'],
                true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                '<?php
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
                '<?php
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
                '7.4',
                ['MissingImmutableAnnotation'],
                true,
            ],
            'dontAddPureAnnotationWhenMethodHasImpurity' => [
                '<?php
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
                '<?php
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
                '7.4',
                ['MissingImmutableAnnotation'],
                true,
            ],
            'addPureAnnotationWhenClassCanHoldMutableData' => [
                '<?php
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
                '<?php
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
                '7.4',
                ['MissingImmutableAnnotation'],
                true,
            ],
            'addPureAnnotationToClassThatExtends' => [
                '<?php
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
                '<?php
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
                '7.4',
                ['MissingImmutableAnnotation'],
                true,
            ],
        ];
    }
}
