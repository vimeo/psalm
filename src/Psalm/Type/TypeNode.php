<?php

declare(strict_types=1);

namespace Psalm\Type;

interface TypeNode
{
    /**
     * @return array<TypeNode>
     */
    public function getChildNodes() : array;
}
