<?php

declare(strict_types=1);

namespace Psalm\Tests\Storage;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
final class SerializationFixtureUsingTraitAgain extends SerializationFixtureParent
{
    use UnserializeMemoryUsageSuppressionTrait;

    /** The parent's private property of the same name is another property */
    private ?string $parent_private = null;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(public readonly int $readonly)
    {
        parent::__construct('parent readonly');
        $this->parent_private = 'own private';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function describe(): array
    {
        return [...parent::describe(), 'own_parent_private' => $this->parent_private, 'readonly' => $this->readonly];
    }
}
