<?php
namespace Psalm\Storage;

use Psalm\Type;

class ClassLikeStorage
{
    /**
     * A lookup table of all public methods in this class
     *
     * @var array<string,bool>
     */
    public $public_class_methods = [];

    /**
     * A lookup table of all protected methods in this class
     *
     * @var array<string,bool>
     */
    public $protected_class_methods = [];

    /**
     * A lookup table for public class properties
     *
     * @var array<string, Type\Union|false>
     */
    public $public_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string, Type\Union|false>
     */
    public $protected_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string, Type\Union|false>
     */
    public $private_class_properties = [];

    /**
     * A lookup table for public class properties
     *
     * @var array<string, Type\Union|false>
     */
    public $public_static_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string, Type\Union|false>
     */
    public $protected_static_class_properties = [];

    /**
     * A lookup table for private class properties
     *
     * @var array<string, Type\Union|false>
     */
    public $private_static_class_properties = [];

    /**
     * A lookup table for public class constants
     *
     * @var array<string, Type\Union>
     */
    public $public_class_constants = [];

    /**
     * A lookup table for protected class constants
     *
     * @var array<string, Type\Union>
     */
    public $protected_class_constants = [];

    /**
     * A lookup table for private class constants
     *
     * @var array<string, Type\Union>
     */
    public $private_class_constants = [];

    /**
     * @var bool
     */
    public $registered = false;

    /**
     * @var bool
     */
    public $reflected = false;

    /**
     * Is this class user-defined
     *
     * @var bool
     */
    public $user_defined;

    /**
     * Interfaces this class implements
     *
     * @var array<string>
     */
    public $class_implements = [];

    /**
     * Parent interfaces
     *
     * @var  array<string>
     */
    public $parent_interfaces = [];

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var string
     */
    public $file_path;

    /**
     * @var array<string, bool>
     */
    public $used_traits = [];

    /**
     * @var array<string, MethodStorage>
     */
    public $methods = [];

    /**
     * @var array<string, string>
     */
    public $declaring_method_ids = [];

    /**
     * @var array<string, array<string>>
     */
    public $overridden_method_ids = [];
}
