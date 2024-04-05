<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

use function array_values;
use function property_exists;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * @psalm-immutable
 */
final class ClassConstantStorage
{
    /** @psalm-suppress MutableDependency Mutable by design */
    use CustomMetadataTrait;
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

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

    /**
     * Used in the Language Server
     */
    public function getHoverMarkdown(string $const): string
    {
        switch ($this->visibility) {
            case ClassLikeAnalyzer::VISIBILITY_PRIVATE:
                $visibility_text = 'private';
                break;

            case ClassLikeAnalyzer::VISIBILITY_PROTECTED:
                $visibility_text = 'protected';
                break;

            default:
                $visibility_text = 'public';
        }

        $value = '';
        if ($this->type) {
            $types = $this->type->getAtomicTypes();
            $type = array_values($types)[0];
            if (property_exists($type, 'value')) {
                /** @psalm-suppress UndefinedPropertyFetch */
                $value = " = {$type->value};";
            }
        }


        return "$visibility_text const $const$value";
    }
}
