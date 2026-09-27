<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner;

use PhpParser\Node\Stmt\ClassMethod;
use Psalm\Storage\Mutations;

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
     * @var list<int> interned namespaces
     */
    public array $psalm_internal = [];

    /**
     * @var string[]
     */
    public array $mixins = [];

    /**
     * @var array<int, array{int, ?string, ?string, bool, int}> interned template name, ...
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
     * @var array<int, array{end?: int, line_number: int, name: int, start?: int, tag: string, type: string}>
     *      name is the interned property name, without the leading $
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

    /** @var Mutations::LEVEL_* */
    public int $allowed_mutations = Mutations::LEVEL_ALL;

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
