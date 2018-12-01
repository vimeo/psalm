<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;

class PropertyStorage
{
    /**
     * @var bool
     */
    public $is_static;

    /**
     * @var int
     */
    public $visibility;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $type_location;

    /**
     * @var Type\Union|false
     */
    public $type;

    /**
     * @var Type\Union|null
     */
    public $suggested_type;

    /**
     * @var bool
     */
    public $has_default = false;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @var bool
     */
    public $internal = false;

    /**
     * @var array<string, array<int, CodeLocation>>|null
     */
    public $referencing_locations;

    public function getInfo() : string
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

        return $visibility_text . ' ' . ($this->type ?: 'mixed');
    }
}
