<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

final class FunctionLikeParameter implements HasAttributesInterface, TypeNode
{
    use CustomMetadataTrait;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $by_ref;

    /**
     * @var Union|null
     */
    public $type;

    /**
     * @var Union|null
     */
    public $out_type;

    /**
     * @var Union|null
     */
    public $signature_type;

    /**
     * @var bool
     */
    public $has_docblock_type = false;

    /**
     * @var bool
     */
    public $is_optional;

    /**
     * @var bool
     */
    public $is_nullable;

    /**
     * @var Union|UnresolvedConstantComponent|null
     */
    public $default_type;

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var CodeLocation|null
     */
    public $type_location;

    /**
     * @var CodeLocation|null
     */
    public $signature_type_location;

    /**
     * @var bool
     */
    public $is_variadic;

    /**
     * @var array<string>|null
     */
    public $sinks;

    /**
     * @var bool
     */
    public $assert_untainted = false;

    /**
     * @var bool
     */
    public $type_inferred = false;

    /**
     * @var bool
     */
    public $expect_variable = false;

    /**
     * @var bool
     */
    public $promoted_property = false;

    /**
     * @var list<AttributeStorage>
     */
    public $attributes = [];

    /**
     * @var ?string
     */
    public $description;

    /**
     * @psalm-external-mutation-free
     * @param Union|UnresolvedConstantComponent|null $default_type
     */
    public function __construct(
        string $name,
        bool $by_ref,
        ?Union $type = null,
        ?Union $signature_type = null,
        ?CodeLocation $location = null,
        ?CodeLocation $type_location = null,
        bool $is_optional = true,
        bool $is_nullable = false,
        bool $is_variadic = false,
        $default_type = null,
        ?Union $out_type = null
    ) {
        $this->name = $name;
        $this->by_ref = $by_ref;
        $this->type = $type;
        $this->signature_type = $signature_type;
        $this->is_optional = $is_optional;
        $this->is_nullable = $is_nullable;
        $this->is_variadic = $is_variadic;
        $this->location = $location;
        $this->type_location = $type_location;
        $this->signature_type_location = $type_location;
        $this->default_type = $default_type;
        $this->out_type = $out_type;
    }

    /** @psalm-mutation-free */
    public function getId(): string
    {
        return ($this->type ? $this->type->getId() : 'mixed')
            . ($this->is_variadic ? '...' : '')
            . ($this->is_optional ? '=' : '');
    }

    /** @psalm-mutation-free */
    public function replaceType(Union $type): self
    {
        if ($this->type === $type) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->type = $type;
        return $cloned;
    }

    /** @psalm-mutation-free */
    public function getChildNodeKeys(): array
    {
        $result = ['type', 'signature_type', 'out_type'];
        if ($this->default_type instanceof Union) {
            $result []= 'default_type';
        }
        return $result;
    }

    /**
     * @psalm-mutation-free
     * @return list<AttributeStorage>
     */
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }
}
