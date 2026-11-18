<?php

declare(strict_types=1);

namespace Psalm\Tests\Storage;

use Override;

/**
 * @psalm-immutable
 */
final class SerializationFixture extends SerializationFixtureParent
{
    private array $private = [];

    protected string $own_protected = '';

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(public readonly int $readonly)
    {
        parent::__construct('parent readonly');
        $this->private = ['private'];
        $this->own_protected = 'own protected';
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function describe(): array
    {
        return [
            ...parent::describe(),
            'private' => $this->private,
            'own_protected' => $this->own_protected,
            'readonly' => $this->readonly,
        ];
    }
}
