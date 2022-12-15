<?php

namespace Psalm\Internal\Scanner;

/**
 * @internal
 */
class FunctionDocblockComment
{
    public ?string $return_type = null;

    public ?string $return_type_description = null;

    public ?int $return_type_start = null;

    public ?int $return_type_end = null;

    public ?int $return_type_line_number = null;

    /**
     * @var array<
     *     int,
     *     array{
     *         name:string,
     *         type:string,
     *         line_number: int,
     *         start: int,
     *         end: int,
     *         description?: string
     *     }
     * >
     */
    public array $params = [];

    /**
     * @var array<int, array{name:string, type:string, line_number: int}>
     */
    public array $params_out = [];

    /**
     * @var array{type:string, line_number: int}|null
     */
    public ?array $self_out = null;

    /**
     * @var array{type:string, line_number: int}|null
     */
    public ?array $if_this_is = null;

    /**
     * @var array<int, array{name:string, type:string, line_number: int}>
     */
    public array $globals = [];

    /**
     * Whether or not the function is deprecated
     */
    public bool $deprecated = false;

    /**
     * If set, the function is internal to the given namespace.
     *
     * @var list<non-empty-string>
     */
    public array $psalm_internal = [];

    /**
     * Whether or not the function is internal
     */
    public bool $internal = false;

    /**
     * Whether or not the function uses get_args
     */
    public bool $variadic = false;

    /**
     * Whether or not the function is pure
     */
    public bool $pure = false;

    /**
     * Whether or not to specialize a given call (useful for taint analysis)
     */
    public bool $specialize_call = false;

    /**
     * Represents the flow from function params to return type
     *
     * @var array<string>
     */
    public array $flows = [];

    /**
     * @var array<string>
     */
    public array $added_taints = [];

    /**
     * @var array<string>
     */
    public array $removed_taints = [];

    /**
     * @var array<int, array{name:string, taint: string}>
     */
    public array $taint_sink_params = [];

    /**
     * @var array<string>
     */
    public array $taint_source_types = [];

    /**
     * @var array<int, array{name:string}>
     */
    public array $assert_untainted_params = [];

    /**
     * Whether or not to ignore the nullability of this function's return type
     */
    public bool $ignore_nullable_return = false;

    /**
     * Whether or not to ignore the nullability of this function's return type
     */
    public bool $ignore_falsable_return = false;

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    /**
     * @var array<int, array{0: string, 1: int, 2: int}>
     */
    public array $throws = [];

    /**
     * @var array<int, array{string, ?string, ?string, bool}>
     */
    public array $templates = [];

    /**
     * @var array<int, array{type: string, param_name: string}>
     */
    public array $assertions = [];

    /**
     * @var array<int, array{type: string, param_name: string}>
     */
    public array $if_true_assertions = [];

    /**
     * @var array<int, array{type: string, param_name: string}>
     */
    public array $if_false_assertions = [];

    public bool $inheritdoc = false;

    public bool $mutation_free = false;

    public bool $external_mutation_free = false;

    public bool $no_named_args = false;

    public bool $stub_override = false;

    public int $since_php_major_version = 0;

    public int $since_php_minor_version = 0;

    public ?string $description = null;

    /** @var array<string, array{lines:list<int>, suggested_replacement?:string}> */
    public array $unexpected_tags = [];
}
