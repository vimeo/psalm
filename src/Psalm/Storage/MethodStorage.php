<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type\Union;

/**
 * @api
 * @psalm-import-type CapabilitySet from Capabilities
 */
final class MethodStorage extends FunctionLikeStorage
{
    use UnserializeMemoryUsageSuppressionTrait;
    public bool $is_static = false;

    /**
     * The properties a constructor without a purity annotation assigns, if it only does that and was given what
     * that needs (see FunctionLikeNodeScanner::applyTrivialConstructorCapabilities())
     *
     * @var list<string>|null
     */
    public ?array $trivial_constructor_properties = null;

    public int $visibility = 0;

    public bool $final = false;

    public bool $final_from_docblock = false;

    public bool $abstract = false;

    public bool $overridden_downstream = false;

    public bool $overridden_somewhere = false;

    public bool $inheritdoc = false;

    public ?bool $inherited_return_type = false;

    public ?string $defining_fqcln = null;

    public bool $has_docblock_param_types = false;

    public bool $has_docblock_return_type = false;

    /**
     * If true, we *assumed* this method is mutation free
     * by looking at its statements, NOT by looking at its docblock/annotations.
     */
    public bool $mutation_free_assumed = false;

    /** @var CapabilitySet */
    public int $containing_class_capabilities = Capabilities::ALL;

    /**
     * The properties of `$this` the body writes: true when it assigns the property itself, false when it only
     * writes items or properties of what it holds
     *
     * @var ?array<string, bool>
     */
    public ?array $this_property_mutations = null;

    /**
     * Of the properties of `$this` whose items, or properties of what they hold, the body writes, what it writes: by
     * property name, the path of properties under it (`''` for its own items, `'a->b'` for `$this->p->a->b`), true
     * when it assigns what the path leads to, false when it only writes items of it
     *
     * @var array<string, array<string, bool>>
     */
    public array $this_property_item_mutations = [];

    /**
     * Whether the body changes nothing but properties of `$this` and local variables: it calls nothing (except to
     * build the exception it throws), so $this_property_mutations is all it writes outside of itself
     */
    public bool $writes_only_this_properties = false;

    /**
     * The static properties the body assigns directly: `self::$name`, `static::$name` and `parent::$name` as written,
     * `lowercased class::$name` for a named class
     *
     * @var ?array<string, bool>
     */
    public ?array $static_property_mutations = null;

    public ?Union $self_out_type = null;

    public ?CodeLocation $self_out_type_location = null;

    public ?Union $if_this_is_type = null;
    public bool $stubbed = false;

    public bool $probably_fluent = false;

    /**
     * Whether the method always returns its receiver: it declares `@return $this`, or it can't be
     * overridden and every one of its returns is `return $this;`
     */
    public bool $returns_this = false;
}
