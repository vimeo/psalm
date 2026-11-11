<?php

declare(strict_types=1);

namespace Psalm\Tests\Storage;

use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @psalm-immutable
 */
abstract class SerializationFixtureParent
{
    use UnserializeMemoryUsageSuppressionTrait;

    public int $public = 0;

    protected string $protected = '';

    private ?string $parent_private = null;

    /** @psalm-suppress PropertyNotSetInConstructor Serialization must keep it uninitialized */
    public int $uninitialized;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(public readonly string $parent_readonly)
    {
        $this->public = 1;
        $this->protected = 'protected';
        $this->parent_private = 'parent private';
    }

    /**
     * The values of the properties of the fixture, to compare the objects before and after serialization
     *
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        return [
            'public' => $this->public,
            'protected' => $this->protected,
            'parent_private' => $this->parent_private,
            'uninitialized_is_set' => isset($this->uninitialized),
            'parent_readonly' => $this->parent_readonly,
        ];
    }
}
