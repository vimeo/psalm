<?php

namespace Psalm\Type\Atomic;

use Psalm\Exception\InvalidConstraintException;

interface IConstrainableType
{
    /**
     * @param mixed $value
     * @throws InvalidConstraintException
     */
    public function setConstraint(?string $name, $value): void;
}
