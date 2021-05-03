<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class NestedTemplateTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'nestedTemplateExtends' => [
                '<?php
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
                '<?php
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
                '<?php
                    /**
                     * @template TInner
                     */
                    interface Wrapper {
                        /** @return TInner */
                        public function unwrap();
                    }

                    /**
                     * @extends Wrapper<string>
                     */
                    interface StringWrapper extends Wrapper {}

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
                '<?php
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
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'nestedTemplateExtendsInvalid' => [
                '<?php
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
