<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

final class FunctionLikeParameter implements HasAttributesInterface, TypeNode
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * Parameter name, without `$`
     *
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
    public function setType(Union $type): self
    {
        if ($this->type === $type) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->type = $type;
        return $cloned;
    }

    /**
     * @internal Should only be used by the MutableTypeVisitor.
     * @psalm-mutation-free
     */
    public function visit(TypeVisitor $visitor): bool
    {
        if ($this->type && !$visitor->traverse($this->type)) {
            return false;
        }
        if ($this->signature_type && !$visitor->traverse($this->signature_type)) {
            return false;
        }
        if ($this->out_type && !$visitor->traverse($this->out_type)) {
            return false;
        }
        if ($this->default_type instanceof Union && !$visitor->traverse($this->default_type)) {
            return false;
        }

        return true;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    public static function visitMutable(MutableTypeVisitor $visitor, &$node, bool $cloned): bool
    {
        foreach (['type', 'signature_type', 'out_type', 'default_type'] as $key) {
            if (!$node->{$key} instanceof TypeNode) {
                continue;
            }

            /** @var TypeNode */
            $value = $node->{$key};
            $value_orig = $value;
            $result = $visitor->traverse($value);
            if ($value !== $value_orig) {
                if (!$cloned) {
                    $node = clone $node;
                    $cloned = true;
                }
                $node->{$key} = $value;
            }

            if (!$result) {
                return false;
            }
        }

        return true;
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
