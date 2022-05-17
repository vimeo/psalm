<?php

namespace Psalm\Issue;

final class InvolvedTypes
{
    private string $inferedType;
    private string $declaredType;

    public function __construct(string $inferedType, string $declaredType)
    {
        $this->inferedType = $inferedType;
        $this->declaredType = $declaredType;
    }

    public function getInferedType(): string
    {
        return $this->inferedType;
    }

    public function getDeclaredType(): string
    {
        return $this->declaredType;
    }

}
