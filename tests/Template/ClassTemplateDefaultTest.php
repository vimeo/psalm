<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ClassTemplateDefaultTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        $factory = '
            /**
             * @template TModel of object
             * @template TCount of int|null = null
             */
            abstract class Factory {
                /** @return TCount */
                public function count() { return null; }

                /** @return (TCount is null|0|1 ? TModel : list<TModel>) */
                public function create() { return null; }
            }

            final class User {}
        ';

        return [
            'extendsOmittingTrailingDefaultedTemplate' => [
                'code' => '<?php' . $factory . '
                    /** @extends Factory<User> */
                    final class UserFactory extends Factory {}

                    $factory = new UserFactory();
                    $count = $factory->count();
                    $created = $factory->create();',
                'assertions' => [
                    '$count' => 'null',
                    '$created' => 'User',
                ],
            ],
            'extendsGivingTrailingDefaultedTemplate' => [
                'code' => '<?php' . $factory . '
                    /** @extends Factory<User, int> */
                    final class UserFactory extends Factory {}

                    $factory = new UserFactory();
                    $count = $factory->count();
                    $created = $factory->create();',
                'assertions' => [
                    '$count' => 'int',
                    '$created' => 'User|list<User>',
                ],
            ],
            'extendsOmittingDefaultedTemplateInGrandchild' => [
                'code' => '<?php' . $factory . '
                    /** @extends Factory<User> */
                    class UserFactory extends Factory {}

                    final class AdminFactory extends UserFactory {}

                    $factory = new AdminFactory();
                    $count = $factory->count();
                    $created = $factory->create();',
                'assertions' => [
                    '$count' => 'null',
                    '$created' => 'User',
                ],
            ],
            'extendsWithoutDocblockBindsDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    abstract class Box {
                        /** @return T */
                        abstract public function get();
                    }

                    final class StringBox extends Box {
                        public function get() { return "a"; }
                    }

                    $box = new StringBox();
                    $value = $box->get();',
                'assertions' => [
                    '$value' => 'string',
                ],
            ],
            'implementsOmittingTrailingDefaultedTemplate' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue = int
                     */
                    interface Source {
                        /** @return TValue */
                        public function first();
                    }

                    /** @implements Source<string> */
                    final class IntSource implements Source {
                        public function first() { return 1; }
                    }

                    $source = new IntSource();
                    $value = $source->first();',
                'assertions' => [
                    '$value' => 'int',
                ],
            ],
            'useOmittingTrailingDefaultedTemplate' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue = int
                     */
                    trait HasFirst {
                        /** @return TValue */
                        public function first() { return 1; }
                    }

                    final class IntSource {
                        /** @use HasFirst<string> */
                        use HasFirst;
                    }

                    $source = new IntSource();
                    $value = $source->first();',
                'assertions' => [
                    '$value' => 'int',
                ],
            ],
            'phpstanTemplateDefaultWithoutSpaces' => [
                'code' => '<?php
                    /**
                     * @phpstan-template T of int|string
                     * @phpstan-template U=bool
                     */
                    abstract class Pair {
                        /** @return U */
                        abstract public function second();
                    }

                    /** @extends Pair<int> */
                    final class IntPair extends Pair {
                        public function second() { return true; }
                    }

                    $pair = new IntPair();
                    $value = $pair->second();',
                'assertions' => [
                    '$value' => 'bool',
                ],
            ],
            'extendsOmittingDefaultAfterSpacedUnionBound' => [
                'code' => '<?php
                    /**
                     * @template K
                     * @template T of int | string = int
                     */
                    abstract class Box {
                        /** @return T */
                        abstract public function get();
                    }

                    /** @extends Box<string> */
                    final class IntBox extends Box {
                        public function get() { return 1; }
                    }

                    $box = new IntBox();
                    $value = $box->get();',
                'assertions' => [
                    '$value' => 'int',
                ],
            ],
            'extendsOmittingZeroDefault' => [
                'code' => '<?php
                    /**
                     * @template K
                     * @template T of int = 0
                     */
                    abstract class Box {
                        /** @return T */
                        abstract public function get();
                    }

                    /** @extends Box<string> */
                    final class ZeroBox extends Box {
                        public function get() { return 0; }
                    }

                    $box = new ZeroBox();
                    $value = $box->get();',
                'assertions' => [
                    '$value===' => '0',
                ],
            ],
            'extendsWithPurityArgumentOmittingDefaultedTemplate' => [
                'code' => '<?php
                    /**
                     * @template K of array-key
                     * @template V of int|null = null
                     * @psalm-purity-template P
                     */
                    abstract class Box {
                        /** @return V */
                        abstract public function value();
                    }

                    /** @extends Box[pure]<string> */
                    final class NullBox extends Box {
                        public function value() { return null; }
                    }

                    $box = new NullBox();
                    $value = $box->value();',
                'assertions' => [
                    '$value' => 'null',
                ],
            ],
            'descriptionAfterBoundIsNotADefault' => [
                'code' => '<?php
                    /**
                     * @template T of int the value, = 5 by default
                     */
                    abstract class Box {
                        /** @return T */
                        abstract public function get();
                    }

                    /** @extends Box<int> */
                    final class IntBox extends Box {
                        public function get() { return 1; }
                    }',
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
            'extendsOmittingTemplateWithoutDefault' => [
                'code' => '<?php
                    /**
                     * @template TModel of object
                     * @template TCount of int|null
                     */
                    abstract class Factory {}

                    final class User {}

                    /** @extends Factory<User> */
                    final class UserFactory extends Factory {}',
                'error_message' => 'MissingTemplateParam',
            ],
            'extendsOmittingNonTrailingTemplateWithDefault' => [
                'code' => '<?php
                    /**
                     * @template TModel = null
                     * @template TCount of int|null
                     */
                    abstract class Factory {}

                    /** @extends Factory<int|null> */
                    final class UserFactory extends Factory {}',
                'error_message' => 'MissingTemplateParam',
            ],
            'genericTypeOmittingTrailingDefaultedTemplate' => [
                'code' => '<?php
                    /**
                     * @template TModel of object
                     * @template TCount of int|null = null
                     */
                    abstract class Factory {}

                    final class User {}

                    /** @param Factory<User> $factory */
                    function f(Factory $factory): void {}',
                'error_message' => 'MissingTemplateParam',
            ],
            'defaultReferencingTemplate' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template U = T
                     */
                    abstract class Box {}',
                'error_message' => 'InvalidDocblock',
            ],
            'defaultWithInvalidType' => [
                'code' => '<?php
                    /**
                     * @template T = array<
                     */
                    abstract class Box {}',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
