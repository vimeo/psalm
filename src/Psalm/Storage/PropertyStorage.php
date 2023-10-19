<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type\Union;

final class PropertyStorage implements HasAttributesInterface
{
    use CustomMetadataTrait;

    public ?bool $is_static = null;

    /**
     * @var ClassLikeAnalyzer::VISIBILITY_*
     */
    public int $visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

    public ?CodeLocation $location = null;

    public ?CodeLocation $stmt_location = null;

    public ?CodeLocation $type_location = null;

    public ?CodeLocation $signature_type_location = null;

    public ?Union $type = null;

    public ?Union $signature_type = null;

    public ?Union $suggested_type = null;

    public bool $has_default = false;

    public bool $deprecated = false;

    public bool $readonly = false;

    /**
     * Whether or not to allow mutation by internal methods
     */
    public bool $allow_private_mutation = false;

    /**
     * @var list<non-empty-string>
     */
    public array $internal = [];

    public ?string $getter_method = null;

    public bool $is_promoted = false;

    /**
     * @var list<AttributeStorage>
     */
    public array $attributes = [];

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    public ?string $description = null;

    public function getInfo(): string
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

        return $visibility_text . ' ' . ($this->type ? $this->type->getId() : 'mixed');
    }

    /**
     * @return list<AttributeStorage>
     */
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }
}
