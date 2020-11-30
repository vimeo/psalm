<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use Psalm\Type;

class AssignedProperty
{
    /**
     * @var Type\Union
     */
    public $property_type;

    /**
     * @var string
     */
    public $id;

    /**
     * @var Type\Union
     */
    public $assignment_type;

    public function __construct(
        Type\Union $property_type,
        string $id,
        Type\Union $assignment_type
    ) {
        $this->property_type = $property_type;
        $this->id = $id;
        $this->assignment_type = $assignment_type;
    }
}
