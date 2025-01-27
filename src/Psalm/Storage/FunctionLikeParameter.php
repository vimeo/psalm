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
    use UnserializeMemoryUsageSuppressionTrait;

    public bool $has_docblock_type = false;

    public ?CodeLocation $signature_type_location = null;

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
     * @param string $name parameter name, without the "$" prefix
     */
    public function __construct(
        public string $name,
        public bool $by_ref,
        public ?Union $type = null,
        public ?Union $signature_type = null,
        public ?CodeLocation $location = null,
        public ?CodeLocation $type_location = null,
        public bool $is_optional = true,
        public bool $is_nullable = false,
        public bool $is_variadic = false,
        public Union|UnresolvedConstantComponent|null $default_type = null,
        public ?Union $out_type = null,
    ) {
        $this->signature_type_location = $type_location;
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
