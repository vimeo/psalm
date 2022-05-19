<?php

namespace Psalm\Internal;

final class InvolvedTypes
{
    private string $inferredType;
    private string $declaredType;

    public function __construct(string $inferredType, string $declaredType)
    {
        $this->inferredType = $inferredType;
        $this->declaredType = $declaredType;
    }

    public function getInferredType(): string
    {
        return $this->inferredType;
    }

    public function getDeclaredType(): string
    {
        return $this->declaredType;
    }
}
