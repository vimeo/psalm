<?php
namespace Psalm\Tests\Template;

use const DIRECTORY_SEPARATOR;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class NestedClassTemplateTest extends TestCase
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
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
