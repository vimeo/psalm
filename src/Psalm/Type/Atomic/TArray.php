<?php
namespace Psalm\Type\Atomic;

class TArray extends \Psalm\Type\Atomic implements Generic
{
    use GenericTrait;

    /**
     * @var string
     */
    public $value = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<int, \Psalm\Type\Union> $type_params
     */
    public function __construct(array $type_params)
    {
        $this->type_params = $type_params;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'array';
    }
}
