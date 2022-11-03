<?php

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class NestedTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     *
     */
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
                    class TeacherRepository extends BaseRepository {}'
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
                    }'
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

                    $result = load(StringWrapper::class);'
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
                    }'
            ],
        ];
    }

    /**
     *
     */
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
                'error_message' => 'InvalidTemplateParam'
            ],
        ];
    }
}
