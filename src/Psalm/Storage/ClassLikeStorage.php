<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Override;
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

/**
 * @api
 */
final class ClassLikeStorage implements HasAttributesInterface
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @var array<int, ClassConstantStorage> constant name id => storage
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
     * @var list<int>
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

    public ?int $mixin_declaring_fqcln = null;

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
     * @var array<int, int> name id => name id (same id)
     */
    public array $direct_class_interfaces = [];

    /**
     * Interfaces this class implements explicitly and implicitly
     *
     * @var array<int, int> name id => name id (same id)
     */
    public array $class_implements = [];

    /**
     * Parent interfaces listed explicitly
     *
     * @var array<int, int> name id => name id (same id)
     */
    public array $direct_interface_parents = [];

    /**
     * Parent interfaces
     *
     * @var  array<int, int> name id => name id (same id)
     */
    public array $parent_interfaces = [];

    /**
     * There can only be one direct parent class
     */
    public ?int $parent_class = null;

    /**
     * Parent classes
     *
     * @var array<int, int> name id => name id (same id)
     */
    public array $parent_classes = [];

    public ?CodeLocation $location = null;

    public ?CodeLocation $stmt_location = null;

    public ?CodeLocation $namespace_name_location = null;

    public bool $abstract = false;

    public bool $final = false;

    public bool $final_from_docblock = false;

    public bool $trait_used = false;

    /**
     * @var array<int, int> name id => name id (same id)
     */
    public array $used_traits = [];

    /**
     * @var array<int, int> alias method name id => method name id
     */
    public array $trait_alias_map = [];

    /**
     * @var array<int, int> alias id => method name id
     */
    public array $trait_alias_map_cased = [];

    /**
     * @var array<int, bool> method name id => final
     */
    public array $trait_final_map = [];

    /**
     * @var array<int, ClassLikeAnalyzer::VISIBILITY_*> method name id => visibility
     */
    public array $trait_visibility_map = [];

    public bool $is_trait = false;

    public bool $is_interface = false;

    public bool $is_enum = false;

    /** @var Mutations::LEVEL_* */
    public int $allowed_mutations = Mutations::LEVEL_ALL;

    public bool $has_mutations_annotation = false;

    public bool $specialize_instance = false;

    /**
     * @var array<int, MethodStorage> method name id => storage
     */
    public array $methods = [];

    /**
     * @var array<int, MethodStorage> method name id => storage
     */
    public array $pseudo_methods = [];

    /**
     * @var array<int, MethodStorage> method name id => storage
     */
    public array $pseudo_static_methods = [];

    public bool $has_children = false;

    /**
     * Maps pseudo method names to the original declaring method identifier
     * The key is the method name as declared, and the value is the original `MethodIdentifier` instance
     *
     * This property contains all pseudo methods declared on ancestors.
     *
     * @var array<int, MethodIdentifier> method name id => method id
     */
    public array $declaring_pseudo_method_ids = [];

    /**
     * @var array<int, MethodIdentifier> method name id => method id
     */
    public array $declaring_method_ids = [];

    /**
     * @var array<int, MethodIdentifier> method name id => method id
     */
    public array $appearing_method_ids = [];

    /**
     * Map from method name to list of declarations in order from parent, to grandparent, to
     * great-grandparent, etc **including traits and interfaces**. Ancestors that don't have their own declaration are
     * skipped.
     *
     * @var array<int, array<int, MethodIdentifier>> method name id => class name id => method id
     */
    public array $overridden_method_ids = [];

    /**
     * @var array<int, MethodIdentifier> method name id => method id
     */
    public array $documenting_method_ids = [];

    /**
     * @var array<int, MethodIdentifier> method name id => method id
     */
    public array $inheritable_method_ids = [];

    /**
     * method name id => class name id => method name id => true
     *
     * @var array<int, array<int, array<int, bool>>>
     */
    public array $potential_declaring_method_ids = [];

    /**
     * @var array<int, PropertyStorage> property name id => storage
     */
    public array $properties = [];

    /**
     * @var array<int, Union> property name id => type
     */
    public array $pseudo_property_set_types = [];

    /**
     * @var array<int, Union> property name id => type
     */
    public array $pseudo_property_get_types = [];

    /**
     * @var array<int, int> property name id => declaring class name id
     */
    public array $declaring_property_ids = [];

    /**
     * @var array<int, int> property name id => appearing class name id
     */
    public array $appearing_property_ids = [];

    public ?Union $inheritors = null;

    /**
     * @var array<int, int> property name id => class name id
     */
    public array $inheritable_property_ids = [];

    /**
     * @var array<int, list<int>> property name id => list of class name ids
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
     * @var array<int, non-empty-array<int, Union>>|null template name id => defining entity id => type
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
     * @var array<int, non-empty-array<int, Union>>|null class name id => offset => type
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
     * @var array<int, array<int, Union>>|null class name id => template name id => type
     */
    public ?array $template_extended_params = null;

    /**
     * @var array<int, int>|null class name id => count
     */
    public ?array $template_type_extends_count = null;


    /**
     * @var array<int, int>|null class name id => count
     */
    public ?array $template_type_implements_count = null;

    public ?Union $yield = null;

    public ?int $declaring_yield_fqcn = null;

    /**
     * @var array<int, int>|null class name id => count
     */
    public ?array $template_type_uses_count = null;

    /**
     * @var array<int, bool> property name id => initialized
     */
    public array $initialized_properties = [];

    /**
     * @var array<int, true> class name id => true
     */
    public array $invalid_dependencies = [];

    /**
     * @var array<int, bool> class name id => true
     */
    public array $dependent_classlikes = [];

    public bool $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public array $docblock_issues = [];

    /**
     * @var array<int, ClassTypeAlias> alias name id => alias
     */
    public array $type_aliases = [];

    public bool $preserve_constructor_signature = false;

    public bool $enforce_template_inheritance = false;

    public ?int $extension_requirement = null;

    /**
     * @var list<int>
     */
    public array $implementation_requirements = [];

    /**
     * @var list<AttributeStorage>
     */
    public array $attributes = [];

    /**
     * @var array<int, EnumCaseStorage> case name id => storage
     */
    public array $enum_cases = [];

    /**
     * @var 'int'|'string'|null
     */
    public ?string $enum_type = null;

    public ?string $description = null;

    public bool $public_api = false;

    public bool $readonly = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(public int $name)
    {
    }

    /**
     * @psalm-mutation-free
     */
    public function isPure(): bool
    {
        return $this->allowed_mutations <= Mutations::LEVEL_NONE;
    }

    /**
     * @psalm-mutation-free
     */
    public function isMutationFree(): bool
    {
        return $this->allowed_mutations <= Mutations::LEVEL_INTERNAL_READ;
    }

    /**
     * @psalm-mutation-free
     */
    public function isExternalMutationFree(): bool
    {
        return $this->allowed_mutations <= Mutations::LEVEL_INTERNAL_READ_WRITE;
    }

    /**
     * @return list<AttributeStorage>
     */
    #[Override]
    public function getAttributeStorages(): array
    {
        return $this->attributes;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasAttributeIncludingParents(
        int $fq_class_name,
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
     * @psalm-mutation-free
     */
    public function getClassTemplateTypes(): array
    {
        $type_params = [];

        foreach ($this->template_types ?? [] as $type_map) {
            $type_params[] = array_values($type_map)[0];
        }

        return $type_params;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasSealedProperties(Config $config): bool
    {
        return $this->sealed_properties ?? ($this->user_defined ? $config->seal_all_properties : false);
    }

    /**
     * @psalm-mutation-free
     */
    public function hasSealedMethods(Config $config): bool
    {
        return $this->sealed_methods ?? ($this->user_defined ? $config->seal_all_methods : false);
    }

    /**
     * @psalm-mutation-free
     */
    private function hasAttribute(int $fq_class_name): bool
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
     * @psalm-mutation-free
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
