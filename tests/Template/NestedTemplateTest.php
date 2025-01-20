<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class NestedTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'nestedTemplateExtends' => [
                'code' => '<?php
                    namespace Foo;

                    interface IBaseViewData {}

                    /**
                     * @template TViewData
                     */
                    class BaseModel {}

                    /**
                     * @template TViewData as IBaseViewData
                     * @template TModel as BaseModel<TViewData>
                     */
                    abstract class BaseRepository {}

                    class StudentViewData implements IBaseViewData {}
                    class TeacherViewData implements IBaseViewData {}

                    /** @extends BaseModel<StudentViewData> */
                    class StudentModel extends BaseModel {}

                    /** @extends BaseModel<TeacherViewData> */
                    class TeacherModel extends BaseModel {}

                    /** @extends BaseRepository<StudentViewData, StudentModel> */
                    class StudentRepository extends BaseRepository {}

                    /** @extends BaseRepository<TeacherViewData, TeacherModel> */
                    class TeacherRepository extends BaseRepository {}',
            ],
            'unwrapIndirectGenericTemplated' => [
                'code' => '<?php
                    /**
                     * @template TInner
                     */
                    interface Wrapper {
                        /** @return TInner */
                        public function unwrap() : object;
                    }

                    /**
                     * @template TInner2
                     * @template TWrapper2 of Wrapper<TInner2>
                     * @param  TWrapper2 $wrapper
                     * @return TInner2
                     */
                    function indirectUnwrap(Wrapper $wrapper) : object {
                        return unwrapGeneric($wrapper);
                    }

                    /**
                     * @template TInner1
                     * @template TWrapper1 of Wrapper<TInner1>
                     * @param TWrapper1 $wrapper
                     * @return TInner1
                     */
                    function unwrapGeneric(Wrapper $wrapper) {
                        return $wrapper->unwrap();
                    }',
            ],
            'unwrapFromTemplatedClassString' => [
                'code' => '<?php
                    /**
                     * @template TInner
                     */
                    interface Wrapper {
                        /** @return TInner */
                        public function unwrap();
                    }

                    /**
                     * @implements Wrapper<string>
                     */
                    class StringWrapper implements Wrapper {
                        public function unwrap() {
                            return "hello";
                        }
                    }

                    /**
                     * @template TInner
                     * @template TWrapper of Wrapper<TInner>
                     *
                     * @param  class-string<TWrapper> $class
                     * @return TInner
                     */
                    function load(string $class) {
                        $package = new $class();
                        return $package->unwrap();
                    }

                    $result = load(StringWrapper::class);',
            ],
            'unwrapNestedTemplateWithReset' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TArray of non-empty-array<TValue>
                     * @param TArray $arr
                     * @return TValue
                     */
                    function toList(array $arr): array {
                        return reset($arr);
                    }',
            ],
            '3levelNestedTemplatesOfMixed' => [
                'code' => '<?php
                    /** @template T */
                    interface A {}

                    /**
                     * @template T
                     * @template U of A<T>
                     */
                    interface B {}

                    /** @template T */
                    interface J {}

                    /**
                     * @template T
                     * @template U of A<T>
                     * @implements J<U>
                     */
                    class K2 implements J {}

                    /**
                     * @template T
                     * @template U of A<T>
                     * @template V of B<T, U>
                     * @extends J<V>
                     */
                    interface K3 extends J {}

                    /**
                     * @template T
                     * @template U of A<T>
                     * @template V of B<T, U>
                     * @implements J<V>
                     */
                    class K1 implements J {}',
            ],
            '4levelNestedTemplatesOfObjects' => [
                'code' => '<?php
                    /**
                     * Interface for all DB entities that map to some data-model object.
                     *
                     * @template T
                     */
                    interface DbEntity
                    {
                        /**
                         * Maps this entity to a data-model entity
                         *
                         * @return T Data-model entity to which this DB entity maps.
                         */
                        public function toCore();
                    }

                    /**
                     * @template T of object
                     */
                    abstract class EntityRepository {}

                    /**
                     * Base entity repository with common tooling.
                     *
                     * @template T of object
                     * @extends EntityRepository<T>
                     */
                    abstract class DbEntityRepository
                    extends EntityRepository {}

                    interface ObjectId {}

                    /**
                     * @template I of ObjectId
                     */
                    interface AnObject {}

                    /**
                     * Base entity repository with common tooling.
                     *
                     * @template I of ObjectId
                     * @template O of AnObject<I>
                     * @template E of DbEntity<O>
                     * @extends DbEntityRepository<E>
                     */
                    abstract class AnObjectEntityRepository
                    extends DbEntityRepository
                    {}

                    /**
                     * Base repository implementation backed by a Db repository.
                     *
                     * @template T
                     * @template E of DbEntity<T>
                     * @template R of DbEntityRepository<E>
                     */
                    abstract class DbRepositoryWrapper
                    {
                        /** @var R $repo Db repository */
                        private DbEntityRepository $repo;

                        /**
                         * Getter for the Db repository.
                         *
                         * @return DbEntityRepository The Db repository.
                         * @psalm-return R
                         */
                        protected function getDbRepo(): DbEntityRepository
                        {
                            return $this->repo;
                        }
                    }

                    /**
                     * Base implementation for all custom repositories that map to Core objects.
                     *
                     * @template I of ObjectId
                     * @template O of AnObject<I>
                     * @template E of DbEntity<O>
                     * @template R of AnObjectEntityRepository<I, O, E>
                     * @extends DbRepositoryWrapper<O, E, R>
                     */
                    abstract class AnObjectDbRepositoryWrapper
                    extends DbRepositoryWrapper {}',
            ],
            '4levelNestedTemplateAsFunctionParameter' => [
                'code' => '<?php
                    /**
                     * Interface for all DB entities that map to some data-model object.
                     *
                     * @template T
                     */
                    interface DbEntity
                    {
                        /**
                         * Maps this entity to a data-model entity
                         *
                         * @return T Data-model entity to which this DB entity maps.
                         */
                        public function toCore();
                    }

                    /**
                     * @template T of object
                     */
                    abstract class EntityRepository {}

                    /**
                     * Base entity repository with common tooling.
                     *
                     * @template T of object
                     * @extends EntityRepository<T>
                     */
                    abstract class DbEntityRepository
                    extends EntityRepository {}

                    interface ObjectId {}

                    /**
                     * @template I of ObjectId
                     */
                    interface AnObject {}

                    /**
                     * Base entity repository with common tooling.
                     *
                     * @template I of ObjectId
                     * @template O of AnObject<I>
                     * @template E of DbEntity<O>
                     * @extends DbEntityRepository<E>
                     */
                    abstract class AnObjectEntityRepository
                    extends DbEntityRepository
                    {}

                    /**
                     * Base repository implementation backed by a Db repository.
                     *
                     * @template T
                     * @template E of DbEntity<T>
                     * @template R of DbEntityRepository<E>
                     */
                    abstract class DbRepositoryWrapper
                    {
                        /** @var R $repo Db repository */
                        private DbEntityRepository $repo;

                        /**
                         * Getter for the Db repository.
                         *
                         * @return DbEntityRepository The Db repository.
                         * @psalm-return R
                         */
                        protected function getDbRepo(): DbEntityRepository
                        {
                            return $this->repo;
                        }
                    }

                    /**
                     * Base implementation for all custom repositories that map to Core objects.
                     *
                     * @template I of ObjectId
                     * @template O of AnObject<I>
                     * @template E of DbEntity<O>
                     * @template R of AnObjectEntityRepository<I, O, E>
                     * @extends DbRepositoryWrapper<O, E, R>
                     */
                    abstract class AnObjectDbRepositoryWrapper
                    extends DbRepositoryWrapper {}

                    abstract class Utilities {
                        /**
                         * @template I of ObjectId
                         * @template O of AnObject<I>
                         * @template E of DbEntity<O>
                         * @template R of AnObjectEntityRepository<I, O, E>
                         * @psalm-param AnObjectDbRepositoryWrapper<I, O, E, R> $repo
                         * @return void
                         */
                        abstract public static function doSomething(AnObjectDbRepositoryWrapper $repo): void;
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'nestedTemplateExtendsInvalid' => [
                'code' => '<?php
                    namespace Foo;

                    interface IBaseViewData {}

                    /**
                     * @template TViewData
                     */
                    class BaseModel {}

                    /**
                     * @template TViewData as IBaseViewData
                     * @template TModel as BaseModel<TViewData>
                     */
                    abstract class BaseRepository {}

                    class StudentViewData implements IBaseViewData {}
                    class TeacherViewData implements IBaseViewData {}

                    /** @extends BaseModel<StudentViewData> */
                    class StudentModel extends BaseModel {}

                    /** @extends BaseModel<TeacherViewData> */
                    class TeacherModel extends BaseModel {}

                    /** @extends BaseRepository<StudentViewData, TeacherModel> */
                    class StudentRepository extends BaseRepository {}',
                'error_message' => 'InvalidTemplateParam',
            ],
        ];
    }
}
