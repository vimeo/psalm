<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use Psalm\Type\Union;

/**
 * @internal
 */
final class AssignedProperty
{
    public function __construct(public Union $property_type, public string $id, public Union $assignment_type)
    {
    }
}
