<?php

namespace Psalm\Type;

interface TypeNode
{
    /**
     * @return list<string>
     */
    public function getChildNodeKeys(): array;
}
