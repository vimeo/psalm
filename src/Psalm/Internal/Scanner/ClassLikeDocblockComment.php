<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Storage\Capabilities;

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
     * The names of the templates declared with `@psalm-purity-template`, whose values are
     * capability sets.
     *
     * @var list<string>
     */
    public array $purity_templates = [];

    /**
     * The default of each purity template that has one, for subclasses that do not bind it.
     *
     * @var array<string, string>
     */
    public array $purity_template_defaults = [];

    /**
     * The lower bound of each purity template that has one: what every value of it requires.
     *
     * @var array<string, string>
     */
    public array $purity_template_lower_bounds = [];

    /**
     * The values of `@psalm-capabilities` tags that are not plain capability lists: purity
     * types, resolved once the type aliases in scope are known.
     *
     * @var list<string>
     */
    public array $capabilities_expressions = [];

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

    /** @var int */
    /** A bitmask of {@see Capabilities} constants */
    public int $capabilities = Capabilities::ALL;

    public bool $has_mutations_annotation = false;

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
