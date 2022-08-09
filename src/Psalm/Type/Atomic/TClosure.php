<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Represents a closure where we know the return type and params
 * @psalm-immutable
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /** @var array<string, bool> */
    public $byref_uses = [];

    /**
     * @param list<FunctionLikeParameter> $params
     * @param array<string, bool> $byref_uses
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null $extra_types
     */
    public function __construct(
        string $value = 'callable',
        ?array $params = null,
        ?Union $return_type = null,
        ?bool $is_pure = null,
        array $byref_uses = [],
        array $extra_types = []
    ) {
        $this->value = $value;
        $this->params = $params;
        $this->return_type = $return_type;
        $this->is_pure = $is_pure;
        $this->byref_uses = $byref_uses;
        $this->extra_types = $extra_types;
    }

    public function setIntersectionTypes(?array $types): TClosure
    {
        return new self($this->value, $this->params, $this->return_type, $this->is_pure, $this->byref_uses, $types);
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
