<?php

namespace Psalm\Internal\Scanner;

use Psalm\Type\Union;

/**
 * @internal
 */
final class VarDocblockComment
{
    public ?Union $type = null;

    public ?string $var_id = null;

    public ?int $line_number = null;

    public ?int $type_start = null;

    public ?int $type_end = null;

    /**
     * Whether or not the property is deprecated
     */
    public bool $deprecated = false;

    /**
     * Whether or not the property is internal
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
     */
    public bool $readonly = false;

    /**
     * Whether or not to allow mutation by internal methods
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

    public ?string $description = null;
}
