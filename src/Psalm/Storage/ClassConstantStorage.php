<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * @psalm-immutable
 */
final class ClassConstantStorage
{
    /** @psalm-suppress MutableDependency Mutable by design */
    use CustomMetadataTrait;
    use ImmutableNonCloneableTrait;

    public ?CodeLocation $type_location;

    /**
     * The type from an annotation, or the inferred type if no annotation exists.
     */
    public ?Union $type;

    /**
     * The type inferred from the value.
     */
    public ?Union $inferred_type;

    /**
     * @var ClassLikeAnalyzer::VISIBILITY_*
     */
    public int $visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

    public ?CodeLocation $location;

    public ?CodeLocation $stmt_location;

    public ?UnresolvedConstantComponent $unresolved_node;

    public bool $deprecated = false;

    public bool $final = false;

    /**
     * @var list<AttributeStorage>
     */
    public array $attributes = [];

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    public ?string $description;

    /**
     * @param ClassLikeAnalyzer::VISIBILITY_* $visibility
     * @param list<AttributeStorage> $attributes
     * @param array<int, string> $suppressed_issues
     */
    public function __construct(
        ?Union $type,
        ?Union $inferred_type,
        int $visibility,
        ?CodeLocation $location,
        ?CodeLocation $type_location = null,
        ?CodeLocation $stmt_location = null,
        bool $deprecated = false,
        bool $final = false,
        ?UnresolvedConstantComponent $unresolved_node = null,
        array $attributes = [],
        array $suppressed_issues = [],
        ?string $description = null
    ) {
        $this->visibility = $visibility;
        $this->location = $location;
        $this->type = $type;
        $this->inferred_type = $inferred_type;
        $this->type_location = $type_location;
        $this->stmt_location = $stmt_location;
        $this->deprecated = $deprecated;
        $this->final = $final;
        $this->unresolved_node = $unresolved_node;
        $this->attributes = $attributes;
        $this->suppressed_issues = $suppressed_issues;
        $this->description = $description;
    }
}
