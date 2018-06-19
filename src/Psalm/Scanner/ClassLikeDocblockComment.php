<?php
namespace Psalm\Scanner;

class ClassLikeDocblockComment
{
    /**
     * Whether or not the class is deprecated
     *
     * @var bool
     */
    public $deprecated = false;

    /**
     * @var array<int, array<int, string>>
     */
    public $template_type_names = [];

    /**
     * @var array<int, string>
     */
    public $template_parents = [];

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
     * @var array<int, string>
     */
    public $suppressed_issues = [];
}
