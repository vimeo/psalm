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
    public $deprecated = false;

    /**
     * Whether or not the class is internal
     *
     * @var bool
     */
    public $internal = false;

    /**
     * Whether or not the class is final
     *
     * @var bool
     */
    public $final = false;

    /**
     * If set, the class is internal to the given namespace.
     *
     * @var list<non-empty-string>
     */
    public $psalm_internal = [];

    /**
     * @var string[]
     */
    public $mixins = [];

    /**
     * @var array<int, array{string, ?string, ?string, bool, int}>
     */
    public $templates = [];

    /**
     * @var array<int, string>
     */
    public $template_extends = [];

    /**
     * @var array<int, string>
     */
    public $template_implements = [];

    /**
     * @var ?string
     */
    public $yield;

    /**
     * @var array<int, array{end?: int, line_number: int, name: string, start?: int, tag: string, type: string}>
     */
    public $properties = [];

    /**
     * @var array<int, ClassMethod>
     */
    public $methods = [];

    /**
     * @var bool
     */
    public $sealed_properties = false;

    /**
     * @var bool
     */
    public $sealed_methods = false;

    /**
     * @var bool
     */
    public $override_property_visibility = false;

    /**
     * @var bool
     */
    public $override_method_visibility = false;

    /**
     * @var bool
     */
    public $mutation_free = false;

    /**
     * @var bool
     */
    public $external_mutation_free = false;

    /**
     * @var bool
     */
    public $taint_specialize = false;

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var list<array{line_number:int,start_offset:int,end_offset:int,parts:list<string>}>
     */
    public $imported_types = [];

    /**
     * @var bool
     */
    public $consistent_constructor = false;

    /**
     * @var bool
     */
    public $consistent_templates = false;

    /** @var bool */
    public $stub_override = false;

    /**
     * @var null|string
     */
    public $extension_requirement;

    /**
     * @var array<int, string>
     */
    public $implementation_requirements = [];

    /**
     * @var ?string
     */
    public $description;
}
