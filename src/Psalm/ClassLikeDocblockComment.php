<?php
namespace Psalm;

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
    public $template_types = [];

    /**
     * @var array<int, array{name:string, type:string}>
     */
    public $properties = [];

    /**
     * @var bool
     */
    public $sealed_properties = false;

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];
}
