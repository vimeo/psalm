<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;

/**
 * @psalm-immutable
 */
final class ImmutableAnnotationAdditionTest extends FileManipulationTestCase
{
    #[Override]
    public function providerValidCodeParse(): array
    {
        return [
            'addImmutableAnnotation' => [
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
            'addImmutableAnnotationTrait' => [
                'input' => '<?php
                    trait A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }
                    class B {
                        use A;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    trait A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }
                    class B {
                        use A;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'noAddImmutableAnnotationTraitUnused' => [
                'input' => '<?php
                    trait A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'output' => '<?php
                    trait A {
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
            'noAddImmutableAnnotationTraitMutable' => [
                'input' => '<?php
                    trait A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function setI(int $i) : void {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }
                    final class B {
                        use A;
                    }',
                'output' => '<?php
                    trait A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function setI(int $i) : void {
                            $this->i = $i;
                        }

                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }
                    final class B {
                        use A;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'addImmutableAnnotationAbstractClass' => [
                'input' => '<?php
                    abstract class A {
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
                    abstract class A {
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
            'SKIPPED-addImmutableAnnotationInterface' => [
                'input' => '<?php
                    interface A {
                        public function getPlus5(): int;
                    }',
                'output' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    interface A {
                        public function getPlus5(): int;
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'noAddImmutableAnnotationEnum' => [
                'input' => '<?php
                    enum A {
                        case A = 1;
                        case B = 2;
                    }',
                'output' => '<?php
                    enum A {
                        case A = 1;
                        case B = 2;
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
                    /**
                     * @psalm-mutable
                     */
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
                    /**
                     * @psalm-mutable
                     */
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
            'doNotAddImmutableWhenInError' => [
                'input' => '<?php
                    class B {
                        public int $i = 5;
                    }

                    $b = new B();
                    $b->i = 6;

                    echo $a->i;',
                'output' => '<?php
                    class B {
                        public int $i = 5;
                    }

                    $b = new B();
                    $b->i = 6;

                    echo $a->i;',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'addPureAnnotationToClassThatExtends' => [
                'input' => '<?php
                    /**
                     * @psalm-immutable
                     */
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
                    /**
                     * @psalm-immutable
                     */
                    class AParent {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function mutate() : void {
                            echo "hello";
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    class A extends AParent {
                        public function getPlus5() {
                            return $this->i + 5;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingImmutableAnnotation'],
                'safe_types' => true,
            ],
            'dontAddPureAnnotationToClassThatExtends' => [

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

                    /** @psalm-mutable */
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

                    /** @psalm-mutable */
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
