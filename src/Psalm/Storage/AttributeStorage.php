<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

/**
 * @psalm-immutable
 */
final class AttributeStorage
{
    use ImmutableNonCloneableTrait;
    public string $fq_class_name;

    /**
     * @var list<AttributeArg>
     */
    public array $args;

    /**
     * @psalm-suppress PossiblyUnusedProperty part of public API
     */
    public CodeLocation $location;

    /**
     * @psalm-suppress PossiblyUnusedProperty part of public API
     */
    public CodeLocation $name_location;

    /**
     * @param list<AttributeArg> $args
     */
    public function __construct(
        string $fq_class_name,
        array $args,
        CodeLocation $location,
        CodeLocation $name_location,
    ) {
        $this->fq_class_name = $fq_class_name;
        $this->args = $args;
        $this->location = $location;
        $this->name_location = $name_location;
    }
}
