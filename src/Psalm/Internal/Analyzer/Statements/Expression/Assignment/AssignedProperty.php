<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use Psalm\Type\Union;

/**
 * @internal
 * @psalm-immutable
 */
final class AssignedProperty
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public Union $property_type, public string $id, public Union $assignment_type)
    {
    }
}
