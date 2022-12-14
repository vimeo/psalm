<?php

namespace Psalm\Internal\Scanner;

use Psalm\Type\Union;

/**
 * @internal
 */
class VarDocblockComment
{
    /**
     * @var ?Union
     */
    public ?Union $type = null;

    /**
     * @var string|null
     */
    public ?string $var_id = null;

    /**
     * @var int|null
     */
    public ?int $line_number = null;

    /**
     * @var int|null
     */
    public ?int $type_start = null;

    /**
     * @var int|null
     */
    public ?int $type_end = null;

    /**
     * Whether or not the property is deprecated
     *
     * @var bool
     */
    public bool $deprecated = false;

    /**
     * Whether or not the property is internal
     *
     * @var bool
     */
    public bool $internal = false;

    /**
     * If set, the property is internal to the given namespace.
     *
     * @var list<non-empty-string>
     */
    public array $psalm_internal = [];

    /**
     * Whether or not the property is readonly
     *
     * @var bool
     */
    public bool $readonly = false;

    /**
     * Whether or not to allow mutation by internal methods
     *
     * @var bool
     */
    public bool $allow_private_mutation = false;

    /**
     * @var list<string>
     */
    public array $removed_taints = [];

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    /**
     * @var ?string
     */
    public ?string $description = null;
}
