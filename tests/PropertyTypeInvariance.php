<?php
namespace Psalm\Tests;

class PropertyTypeInvariance extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'validcode' =>
                ['<?php

                class ParentClass
                {
                    /** @var null|string */
                    protected $mightExist;

                    protected ?string $mightExistNative = null;

                    /** @var string */
                    protected $doesExist = "";

                    protected string $doesExistNative = "";
                }

                class ChildClass extends ParentClass
                {
                    /** @var null|string */
                    protected $mightExist = "";

                    protected ?string $mightExistNative = null;

                    /** @var string */
                    protected $doesExist = "";

                    protected string $doesExistNative = "";
                }
                '],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'variantProperties' => [
                '
                <?php

                class ParentClass
                {
                    /** @var null|string */
                    protected $mightExist;

                    protected ?string $mightExistNative = null;

                    /** @var string */
                    protected $doesExist = "";

                    protected string $doesExistNative = "";
                }

                class ChildClass extends ParentClass
                {
                    /** @var string */
                    protected $mightExist = "";

                    protected string $mightExistNative = "";

                    /** @var null|string */
                    protected $doesExist = "";

                    protected ?string $doesExistNative = "";
                }
                ',
                'error_message' => 'NonInvariantPropertyType',
            ]
        ];
    }
}
