<?php

namespace Psalm\Tests;

use Psalm\InvolvedTypes;

class InvolvedTypesTest extends TestCase
{
    public function testCreate(): void
    {
        $inferredType = 'inferred-payload';
        $declaredType = 'declared-payload';
        $involveType = new InvolvedTypes($inferredType, $declaredType);
        $this->assertEquals($inferredType, $involveType->getInferredType());
        $this->assertEquals($declaredType, $involveType->getDeclaredType());
    }
}
