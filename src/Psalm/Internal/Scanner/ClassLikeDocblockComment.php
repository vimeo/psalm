<?php
namespace Psalm\Internal\Scanner;

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
     * If set, the class is internal to the given namespace.
     *
     * @var null|string
     */
    public $psalm_internal = null;

    /**
     * @var array<int, array{string, ?string, ?string, bool}>
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
     * @var array<int, array{name:string, type:string, tag:string, line_number:int}>
     */
    public $properties = [];

    /**
     * @var array<int, \PhpParser\Node\Stmt\ClassMethod>
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
     * @var array<int, string>
     */
    public $suppressed_issues = [];
}
