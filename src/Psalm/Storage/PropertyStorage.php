<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Override;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type\Union;

final class PropertyStorage implements HasAttributesInterface
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    public ?bool $is_static = null;

    /**
     * @var ClassLikeAnalyzer::VISIBILITY_*
     */
    public int $visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

    /**
     * The visibility required to write to the property (PHP 8.4 asymmetric visibility).
     *
     * Always at least as restrictive as $visibility. Equal to $visibility when no
     * explicit set visibility (e.g. `private(set)`) was declared.
     *
     * @var ClassLikeAnalyzer::VISIBILITY_*
     */
    public int $set_visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

    /**
     * Whether the property is declared `final`, either explicitly or implicitly
     * (properties declared `private(set)` are implicitly final).
     */
    public bool $is_final = false;

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

    public ?PropertyHookStorage $hook_get = null;
    public ?PropertyHookStorage $hook_set = null;

    public function getInfo(): string
    {
        $visibility_text = self::getVisibilityText($this->visibility);

        if ($this->set_visibility !== $this->visibility) {
            $visibility_text .= ' ' . self::getVisibilityText($this->set_visibility) . '(set)';
        }

        return $visibility_text . ' ' . ($this->type ? $this->type->getId() : 'mixed');
    }

    /**
     * @param ClassLikeAnalyzer::VISIBILITY_* $visibility
     */
    public static function getVisibilityText(int $visibility): string
    {
        return match ($visibility) {
            ClassLikeAnalyzer::VISIBILITY_PRIVATE => 'private',
            ClassLikeAnalyzer::VISIBILITY_PROTECTED => 'protected',
            default => 'public',
        };
    }

    public function hasAsymmetricVisibility(): bool
    {
        return $this->set_visibility !== $this->visibility;
    }

    /**
     * @return list<AttributeStorage>
     */
    #[Override]
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }
}
