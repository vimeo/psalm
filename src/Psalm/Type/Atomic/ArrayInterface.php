<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

interface ArrayInterface
{
    /**
     * Get minimum number of elements.
     */
    public function getMinCount(): int;
    /**
     * Get maximum number of elements.
     *
     * null means no limit.
     */
    public function getMaxCount(): ?int;
    /**
     * Get exact number of elements.
     */
    public function getCount(): ?int;
    public function isNonEmpty(): bool;
    public function isEmpty(): bool;
    public function getGenericValueType(): Union;
    public function getGenericKeyType(): Union;
}
