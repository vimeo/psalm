<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use Psalm\Type\Union;

/**
 * @internal
 */
class AssignedProperty
{
    public Union $property_type;

    public string $id;

    public Union $assignment_type;

    public function __construct(
        Union $property_type,
        string $id,
        Union $assignment_type
    ) {
        $this->property_type = $property_type;
        $this->id = $id;
        $this->assignment_type = $assignment_type;
    }
}
