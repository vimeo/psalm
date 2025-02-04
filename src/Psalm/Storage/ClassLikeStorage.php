<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TypeAlias\ClassTypeAlias;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\DeprecatedClass;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_values;
use function in_array;

final class ClassLikeStorage implements HasAttributesInterface
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @var array<string, ClassConstantStorage>
     */
    public array $constants = [];

    /**
     * Aliases to help Psalm understand constant refs
     */
    public ?Aliases $aliases = null;

    public bool $populated = false;

    public bool $stubbed = false;

    public bool $deprecated = false;

    /**
     * @var list<non-empty-string>
     */
    public array $internal = [];

    /**
     * @var TTemplateParam[]
     */
    public array $templatedMixins = [];

    /**
     * @var list<TNamedObject>
     */
    public array $namedMixins = [];

    public ?string $mixin_declaring_fqcln = null;

    public ?bool $sealed_properties = null;

    public ?bool $sealed_methods = null;

    public bool $override_property_visibility = false;

    public bool $override_method_visibility = false;

    /**
     * @var array<int, string>
     */
    public array $suppressed_issues = [];

    /**
     * Is this class user-defined
     */
    public bool $user_defined = false;

    /**
     * Interfaces this class implements directly
     *
     * @var array<lowercase-string, string>
     */
    public array $direct_class_interfaces = [];

    /**
     * Interfaces this class implements explicitly and implicitly
     *
     * @var array<lowercase-string, string>
     */
    public array $class_implements = [];

    /**
     * Parent interfaces listed explicitly
     *
     * @var array<lowercase-string, string>
     */
    public array $direct_interface_parents = [];

    /**
     * Parent interfaces
     *
     * @var  array<lowercase-string, string>
     */
    public array $parent_interfaces = [];

    /**
     * There can only be one direct parent class
     */
    public ?string $parent_class = null;

    /**
     * Parent classes
     *
     * @var array<lowercase-string, string>
     */
    public array $parent_classes = [];

    public ?CodeLocation $location = null;

    public ?CodeLocation $stmt_location = null;

    public ?CodeLocation $namespace_name_location = null;

    public bool $abstract = false;

    public bool $final = false;

    public bool $final_from_docblock = false;

    /**
     * @var array<lowercase-string, string>
     */
    public array $used_traits = [];

    /**
     * @var array<lowercase-string, lowercase-string>
     */
    public array $trait_alias_map = [];

    /**
     * @var array<string, string>
     */
    public array $trait_alias_map_cased = [];

    /**
     * @var array<lowercase-string, bool>
     */
    public array $trait_final_map = [];

    /**
     * @var array<string, ClassLikeAnalyzer::VISIBILITY_*>
     */
    public array $trait_visibility_map = [];

    public bool $is_trait = false;

    public bool $is_interface = false;

    public bool $is_enum = false;

    public bool $external_mutation_free = false;

    public bool $mutation_free = false;

    public bool $specialize_instance = false;

    /**
     * @var array<lowercase-string, MethodStorage>
     */
    public array $methods = [];

    /**
     * @var array<lowercase-string, MethodStorage>
     */
    public array $pseudo_methods = [];

    /**
     * @var array<lowercase-string, MethodStorage>
     */
    public array $pseudo_static_methods = [];

    /**
     * Maps pseudo method names to the original declaring method identifier
     * The key is the method name in lowercase, and the value is the original `MethodIdentifier` instance
     *
     * This property contains all pseudo methods declared on ancestors.
     *
     * @var array<lowercase-string, MethodIdentifier>
     */
    public array $declaring_pseudo_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public array $declaring_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public array $appearing_method_ids = [];

    /**
     * Map from lowercase method name to list of declarations in order from parent, to grandparent, to
     * great-grandparent, etc **including traits and interfaces**. Ancestors that don't have their own declaration are
     * skipped.
     *
     * @var array<lowercase-string, array<string, MethodIdentifier>>
     */
    public array $overridden_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public array $documenting_method_ids = [];

    /**
     * @var array<lowercase-string, MethodIdentifier>
     */
    public array $inheritable_method_ids = [];

    /**
     * @var array<lowercase-string, array<string, bool>>
     */
    public array $potential_declaring_method_ids = [];

    /**
     * @var array<string, PropertyStorage>
     */
    public array $properties = [];

    /**
     * @var array<string, Union>
     */
    public array $pseudo_property_set_types = [];

    /**
     * @var array<string, Union>
     */
    public array $pseudo_property_get_types = [];

    /**
     * @var array<string, string>
     */
    public array $declaring_property_ids = [];

    /**
     * @var array<string, string>
     */
    public array $appearing_property_ids = [];

    public ?Union $inheritors = null;

    /**
     * @var array<string, string>
     */
    public array $inheritable_property_ids = [];

    /**
     * @var array<string, array<string>>
     */
    public array $overridden_property_ids = [];

    /**
     * An array holding the class template "as" types.
     *
     * It's the de-facto list of all templates on a given class.
     *
     * The name of the template is the first key. The nested array is keyed by the defining class
     * (i.e. the same as the class name). This allows operations with the same-named template defined
     * across multiple classes to not run into trouble.
     *
     * @var array<string, non-empty-array<string, Union>>|null
     */
    public ?array $template_types = null;

    /**
     * @var array<int, bool>|null
     */
    public ?array $template_covariants = null;

    /**
     * A map of which generic classlikes are extended or implemented by this class or interface.
     *
     * This is only used in the populator, which poulates the $template_extended_params property below.
     *
     * @internal
     * @var array<string, non-empty-array<int, Union>>|null
     */
    public ?array $template_extended_offsets = null;

    /**
     * A map of which generic classlikes are extended or implemented by this class or interface.
     *
     * The annotation "@extends Traversable<SomeClass, SomeOtherClass>" would generate an entry of
     *
     * [
     *     "Traversable" => [
     *         "TKey" => new Union([new TNamedObject("SomeClass")]),
     *         "TValue" => new Union([new TNamedObject("SomeOtherClass")])
     *     ]
     * ]
     *
     * @var array<string, array<string, Union>>|null
     */
    public ?array $template_extended_params = null;

    /**
     * @var array<string, int>|null
     */
    public ?array $template_type_extends_count = null;


    /**
     * @var array<string, int>|null
     */
    public ?array $template_type_implements_count = null;

    public ?Union $yield = null;

    public ?string $declaring_yield_fqcn = null;

    /**
     * @var array<string, int>|null
     */
    public ?array $template_type_uses_count = null;

    /**
     * @var array<string, bool>
     */
    public array $initialized_properties = [];

    /**
     * @var array<string, true>
     */
    public array $invalid_dependencies = [];

    /**
     * @var array<lowercase-string, bool>
     */
    public array $dependent_classlikes = [];

    /**
     * A hash of the source file's name, contents, and this file's modified on date
     */
    public string $hash = '';

    public bool $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public array $docblock_issues = [];

    /**
     * @var array<string, ClassTypeAlias>
     */
    public array $type_aliases = [];

    public bool $preserve_constructor_signature = false;

    public bool $enforce_template_inheritance = false;

    public ?string $extension_requirement = null;

    /**
     * @var array<int, string>
     */
    public array $implementation_requirements = [];

    /**
     * @var list<AttributeStorage>
     */
    public array $attributes = [];

    /**
     * @var array<string, EnumCaseStorage>
     */
    public array $enum_cases = [];

    /**
     * @var 'int'|'string'|null
     */
    public ?string $enum_type = null;

    public ?string $description = null;

    public bool $public_api = false;

    public bool $readonly = false;

    public function __construct(public string $name)
    {
    }

    /**
     * @return list<AttributeStorage>
     */
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }

    public function hasAttributeIncludingParents(
        string $fq_class_name,
        Codebase $codebase,
    ): bool {
        if ($this->hasAttribute($fq_class_name)) {
            return true;
        }

        foreach ($this->parent_classes as $parent_class) {
            // skip missing dependencies
            if (!$codebase->classlike_storage_provider->has($parent_class)) {
                continue;
            }
            $parent_class_storage = $codebase->classlike_storage_provider->get($parent_class);
            if ($parent_class_storage->hasAttribute($fq_class_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the template constraint types for the class.
     *
     * @return list<Union>
     */
    public function getClassTemplateTypes(): array
    {
        $type_params = [];

        foreach ($this->template_types ?? [] as $type_map) {
            $type_params[] = array_values($type_map)[0];
        }

        return $type_params;
    }

    public function hasSealedProperties(Config $config): bool
    {
        return $this->sealed_properties ?? $config->seal_all_properties;
    }

    public function hasSealedMethods(Config $config): bool
    {
        return $this->sealed_methods ?? $config->seal_all_methods;
    }

    private function hasAttribute(string $fq_class_name): bool
    {
        foreach ($this->attributes as $attribute) {
            if ($fq_class_name === $attribute->fq_class_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function getSuppressedIssuesForTemplateExtendParams(): array
    {
        $allowed_issue_types = [
            DeprecatedClass::getIssueType(),
        ];
        $suppressed_issues_for_template_extend_params = [];
        foreach ($this->suppressed_issues as $offset => $suppressed_issue) {
            if (!in_array($suppressed_issue, $allowed_issue_types, true)) {
                continue;
            }
            $suppressed_issues_for_template_extend_params[$offset] = $suppressed_issue;
        }
        return $suppressed_issues_for_template_extend_params;
    }
}
