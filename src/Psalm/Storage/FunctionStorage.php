<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * @api
 */
final class FunctionStorage extends FunctionLikeStorage
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @var array<string, bool> */
    public array $byref_uses = [];

    /**
     * How what the function returns starts, for the names of the keyed stores (see StringStartScanner)
     *
     * @internal
     */
    public ?string $return_string_start = null;

    /**
     * Of a closure: the properties of `$this` its body assigns (see MethodStorage::$this_property_mutations)
     *
     * @internal
     * @var ?array<string, bool>
     */
    public ?array $this_property_mutations = null;

    /**
     * Of a closure: the methods of `$this` its body calls, by lowercase name
     *
     * @internal
     * @var array<lowercase-string, true>
     */
    public array $this_method_calls = [];

    /**
     * Of a closure: whether its body changes nothing but properties of `$this` and local variables, except through
     * the methods of `$this` and the named functions it calls (see MethodStorage::$writes_only_this_properties)
     *
     * @internal
     */
    public bool $writes_only_this_properties = false;
}
