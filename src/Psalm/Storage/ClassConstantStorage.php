<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class ClassConstantStorage
{
    use CustomMetadataTrait;

    /**
     * @var ?CodeLocation
     */
    public $type_location;

    /**
     * The type from an annotation, or the inferred type if no annotation exists.
     *
     * @var ?Union
     */
    public $type;

    /**
     * The type inferred from the value.
     *
     * @var ?Union
     */
    public $inferred_type;

    /**
     * @var ClassLikeAnalyzer::VISIBILITY_*
     */
    public $visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

    /**
     * @var ?CodeLocation
     */
    public $location;

    /**
     * @var ?CodeLocation
     */
    public $stmt_location;

    /**
     * @var ?UnresolvedConstantComponent
     */
    public $unresolved_node;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @var list<AttributeStorage>
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $attributes = [];

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var ?string
     */
    public $description;

    /**
     * @param ClassLikeAnalyzer::VISIBILITY_* $visibility
     */
    public function __construct(?Union $type, ?Union $inferred_type, int $visibility, ?CodeLocation $location)
    {
        $this->visibility = $visibility;
        $this->location = $location;
        $this->type = $type;
        $this->inferred_type = $inferred_type;
    }
}
