<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

class ClassConstantStorage
{
    /**
     * @var ?Union
     */
    public $type;

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
     * @var ?string
     */
    public $description;

    /**
     * @param ClassLikeAnalyzer::VISIBILITY_* $visibility
     */
    public function __construct(?Union $type, int $visibility, ?CodeLocation $location)
    {
        $this->visibility = $visibility;
        $this->location = $location;
        $this->type = $type;
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
                $value = " = {$type->value};";
            }
        }


        return "$visibility_text const $const$value";
    }
}
