<?php

namespace Psalm\Internal\Scanner;

use PhpParser\Node\Stmt\ClassMethod;

/**
 * @internal
 */
class ClassLikeDocblockComment
{
    /**
     * Whether or not the class is deprecated
     *
     * @var bool
     */
    public bool $deprecated = false;

    /**
     * Whether or not the class is internal
     *
     * @var bool
     */
    public bool $internal = false;

    /**
     * Whether or not the class is final
     *
     * @var bool
     */
    public bool $final = false;

    /**
     * If set, the class is internal to the given namespace.
     *
     * @var list<non-empty-string>
     */
    public array $psalm_internal = [];

    /**
     * @var string[]
     */
    public array $mixins = [];

    /**
     * @var array<int, array{string, ?string, ?string, bool, int}>
     */
    public array $templates = [];

    /**
     * @var array<int, string>
     */
    public array $template_extends = [];

    /**
     * @var array<int, string>
     */
    public array $template_implements = [];

    /**
     * @var ?string
     */
    public ?string $yield = null;

    /**
     * @var array<int, array{end?: int, line_number: int, name: string, start?: int, tag: string, type: string}>
     */
    public array $properties = [];

    /**
     * @var array<int, ClassMethod>
     */
    public array $methods = [];

    /**
     * @var bool
     */
    public bool $sealed_properties = false;

    /**
     * @var bool
     */
    public bool $sealed_methods = false;

    /**
     * @var bool
     */
    public bool $override_property_visibility = false;

    /**
     * @var bool
     */
    public bool $override_method_visibility = false;

    /**
     * @var bool
     */
    public bool $mutation_free = false;

    /**
     * @var bool
     */
    public bool $external_mutation_free = false;

    /**
     * @var bool
     */
    public bool $taint_specialize = false;

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    /**
     * @var list<array{line_number:int,start_offset:int,end_offset:int,parts:list<string>}>
     */
    public array $imported_types = [];

    /**
     * @var bool
     */
    public bool $consistent_constructor = false;

    /**
     * @var bool
     */
    public bool $consistent_templates = false;

    /** @var bool */
    public bool $stub_override = false;

    /**
     * @var null|string
     */
    public ?string $extension_requirement = null;

    /**
     * @var array<int, string>
     */
    public array $implementation_requirements = [];

    /**
     * @var ?string
     */
    public ?string $description = null;
}
