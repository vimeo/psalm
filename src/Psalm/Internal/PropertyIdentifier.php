<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Override;
use Psalm\Interner;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Stringable;

/**
 * @psalm-immutable
 * @internal
 */
final class PropertyIdentifier implements Stringable
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param int $fq_class_name interned class name
     * @param int $property_name interned property name (case sensitive)
     * @psalm-mutation-free
     */
    public function __construct(public readonly int $fq_class_name, public readonly int $property_name)
    {
    }

    /** @return non-empty-string */
    #[Override]
    public function __toString(): string
    {
        return Interner::str($this->fq_class_name) . '::$' . Interner::str($this->property_name);
    }
}
