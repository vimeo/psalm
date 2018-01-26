<?php
namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type;

class ClassLikeStorage
{
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
    public $populated = false;

    /**
     * @var bool
     */
    public $all_properties_set_in_constructor = false;

    /**
     * @var bool
     */
    public $deprecated = false;

    /**
     * @var bool
     */
    public $sealed_properties = false;

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var string
     */
    public $name;

    /**
     * Is this class user-defined
     *
     * @var bool
     */
    public $user_defined = false;

    /**
     * Interfaces this class implements
     *
     * @var array<string, string>
     */
    public $class_implements = [];

    /**
     * Parent interfaces
     *
     * @var  array<string, string>
     */
    public $parent_interfaces = [];

    /**
     * Parent interfaces
     *
     * @var  array<string>
     */
    public $parent_classes = [];

    /**
     * @var CodeLocation|null
     */
    public $location;

    /**
     * @var bool
     */
    public $abstract = false;

    /**
     * @var bool
     */
    public $final = false;

    /**
     * @var array<string, string>
     */
    public $used_traits = [];

    /**
     * @var array<string, string>
     */
    public $trait_alias_map = [];

    /**
     * @var bool
     */
    public $is_trait = false;

    /**
     * @var array<string, MethodStorage>
     */
    public $methods = [];

    /**
     * @var array<string, string>
     */
    public $declaring_method_ids = [];

    /**
     * @var array<string, string>
     */
    public $appearing_method_ids = [];

    /**
     * @var array<string, array<string>>
     */
    public $overridden_method_ids = [];

    /**
     * @var array<string, array<string>>
     */
    public $interface_method_ids = [];

    /**
     * @var array<string, string>
     */
    public $inheritable_method_ids = [];

    /**
     * @var array<string, PropertyStorage>
     */
    public $properties = [];

    /**
     * @var array<string, Type\Union>
     */
    public $pseudo_property_set_types = [];

    /**
     * @var array<string, Type\Union>
     */
    public $pseudo_property_get_types = [];

    /**
     * @var array<string, string>
     */
    public $declaring_property_ids = [];

    /**
     * @var array<string, string>
     */
    public $appearing_property_ids = [];

    /**
     * @var array<string, string>
     */
    public $inheritable_property_ids = [];

    /**
     * @var array<string, string>|null
     */
    public $template_types;

    /**
     * @var array<string, array<int, CodeLocation>>|null
     */
    public $referencing_locations;

    /**
     * @var array<string, bool>
     */
    public $initialized_properties = [];
}
