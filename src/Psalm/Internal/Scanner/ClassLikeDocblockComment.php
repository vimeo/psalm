<?php

namespace Psalm\Internal\Scanner;

use PhpParser\Node\Stmt\ClassMethod;

/**
 * @internal
 */
final class ClassLikeDocblockComment
{
    /**
     * Whether or not the class is deprecated
     */
    public bool $deprecated = false;

    /**
     * Whether or not the class is internal
     */
    public bool $internal = false;

    /**
     * Whether or not the class is final
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

    public ?string $yield = null;

    /**
     * @var array<int, array{end?: int, line_number: int, name: string, start?: int, tag: string, type: string}>
     */
    public array $properties = [];

    /**
     * @var array<int, ClassMethod>
     */
    public array $methods = [];

    public ?bool $sealed_properties = null;

    public ?bool $sealed_methods = null;

    public bool $override_property_visibility = false;

    public bool $override_method_visibility = false;

    public bool $mutation_free = false;

    public bool $external_mutation_free = false;

    public bool $taint_specialize = false;

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    /**
     * @var list<array{line_number:int,start_offset:int,end_offset:int,parts:list<string>}>
     */
    public array $imported_types = [];

    public ?string $inheritors = null;

    public bool $consistent_constructor = false;

    public bool $consistent_templates = false;

    public bool $stub_override = false;

    public ?string $extension_requirement = null;

    /**
     * @var array<int, string>
     */
    public array $implementation_requirements = [];

    public ?string $description = null;

    public bool $public_api = false;
}
