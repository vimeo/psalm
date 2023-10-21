<?php

declare(strict_types=1);

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

    public string $name;

    public bool $by_ref;

    public ?Union $type = null;

    public ?Union $out_type = null;

    public ?Union $signature_type = null;

    public bool $has_docblock_type = false;

    public bool $is_optional;

    public bool $is_nullable;

    public Union|UnresolvedConstantComponent|null $default_type = null;

    public ?CodeLocation $location = null;

    public ?CodeLocation $type_location = null;

    public ?CodeLocation $signature_type_location = null;

    public bool $is_variadic;

    /**
     * @var array<string>|null
     */
    public ?array $sinks = null;

    public bool $assert_untainted = false;

    public bool $type_inferred = false;

    public bool $expect_variable = false;

    public bool $promoted_property = false;

    /**
     * @var list<AttributeStorage>
     */
    public array $attributes = [];

    public ?string $description = null;

    /**
     * @psalm-external-mutation-free
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
        Union|UnresolvedConstantComponent|null $default_type = null,
        ?Union $out_type = null,
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
