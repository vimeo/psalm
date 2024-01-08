<?php

namespace Psalm\Type;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\TypeVisitor\FromDocblockSetter;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

use function get_object_vars;

/**
 * @psalm-immutable
 * @psalm-type TProperties=array{
 *      from_docblock?: bool,
 *      from_calculation?: bool,
 *      from_property?: bool,
 *      from_static_property?: bool,
 *      initialized?: bool,
 *      initialized_class?: ?string,
 *      checked?: bool,
 *      failed_reconciliation?: bool,
 *      ignore_nullable_issues?: bool,
 *      ignore_falsable_issues?: bool,
 *      ignore_isset?: bool,
 *      possibly_undefined?: bool,
 *      possibly_undefined_from_try?: bool,
 *      explicit_never?: bool,
 *      had_template?: bool,
 *      from_template_default?: bool,
 *      by_ref?: bool,
 *      reference_free?: bool,
 *      allow_mutations?: bool,
 *      has_mutations?: bool,
 *      different?: bool,
 *      parent_nodes?: array<string, DataFlowNode>
 * }
 */
final class Union implements TypeNode
{
    use ImmutableNonCloneableTrait;
    use UnionTrait;

    /**
     * @psalm-readonly
     * @var non-empty-array<string, Atomic>
     */
    private array $types;

    /**
     * Whether the type originated in a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * Whether the type originated from integer calculation
     *
     * @var bool
     */
    public $from_calculation = false;

    /**
     * Whether the type originated from a property
     *
     * This helps turn isset($foo->bar) into a different sort of issue
     *
     * @var bool
     */
    public $from_property = false;

    /**
     * Whether the type originated from *static* property
     *
     * Unlike non-static properties, static properties have no prescribed place
     * like __construct() to be initialized in
     *
     * @var bool
     */
    public $from_static_property = false;

    /**
     * Whether the property that this type has been derived from has been initialized in a constructor
     *
     * @var bool
     */
    public $initialized = true;

    /**
     * Which class the type was initialised in
     *
     * @var ?string
     */
    public $initialized_class;

    /**
     * Whether or not the type has been checked yet
     *
     * @var bool
     */
    public $checked = false;

    /**
     * @var bool
     */
    public $failed_reconciliation = false;

    /**
     * Whether or not to ignore issues with possibly-null values
     *
     * @var bool
     */
    public $ignore_nullable_issues = false;

    /**
     * Whether or not to ignore issues with possibly-false values
     *
     * @var bool
     */
    public $ignore_falsable_issues = false;

    /**
     * Whether or not to ignore issues with isset on this type
     *
     * @var bool
     */
    public $ignore_isset = false;

    /**
     * Whether or not this variable is possibly undefined
     *
     * @var bool
     */
    public $possibly_undefined = false;

    /**
     * Whether or not this variable is possibly undefined
     *
     * @var bool
     */
    public $possibly_undefined_from_try = false;

    /**
     * whether this type had never set explicitly
     * since it's the bottom type, it's combined into everything else and lost
     *
     * @var bool
     */
    public $explicit_never = false;

    /**
     * Whether or not this union had a template, since replaced
     *
     * @var bool
     */
    public $had_template = false;

    /**
     * Whether or not this union comes from a template "as" default
     *
     * @var bool
     */
    public $from_template_default = false;

    /**
     * @var array<string, TLiteralString>
     */
    private array $literal_string_types = [];

    /**
     * @var array<string, TClassString>
     */
    private array $typed_class_strings = [];

    /**
     * @var array<string, TLiteralInt>
     */
    private array $literal_int_types = [];

    /**
     * @var array<string, TLiteralFloat>
     */
    private array $literal_float_types = [];

    /**
     * True if the type was passed or returned by reference, or if the type refers to an object's
     * property or an item in an array. Note that this is not true for locally created references
     * that don't refer to properties or array items (see Context::$references_in_scope).
     *
     * @var bool
     */
    public $by_ref = false;

    /**
     * @var bool
     */
    public $reference_free = false;

    /**
     * @var bool
     */
    public $allow_mutations = true;

    /**
     * @var bool
     */
    public $has_mutations = true;

    /**
     * This is a cache of getId on non-exact mode
     */
    private ?string $id = null;

    /**
     * This is a cache of getId on exact mode
     */
    private ?string $exact_id;


    /**
     * @var array<string, DataFlowNode>
     */
    public $parent_nodes = [];

    public bool $propagate_parent_nodes = false;

    /**
     * @var bool
     */
    public $different = false;

    private const PROPERTY_KEYS_FOR_UNSERIALIZE = [
        "\0" . self::class . "\0" . 'types' => 'types',
        'from_docblock' => 'from_docblock',
        'from_calculation' => 'from_calculation',
        'from_property' => 'from_property',
        'from_static_property' => 'from_static_property',
        'initialized' => 'initialized',
        'initialized_class' => 'initialized_class',
        'checked' => 'checked',
        'failed_reconciliation' => 'failed_reconciliation',
        'ignore_nullable_issues' => 'ignore_nullable_issues',
        'ignore_falsable_issues' => 'ignore_falsable_issues',
        'ignore_isset' => 'ignore_isset',
        'possibly_undefined' => 'possibly_undefined',
        'possibly_undefined_from_try' => 'possibly_undefined_from_try',
        'explicit_never' => 'explicit_never',
        'had_template' => 'had_template',
        'from_template_default' => 'from_template_default',
        "\0" . self::class . "\0" . 'literal_string_types' => 'literal_string_types',
        "\0" . self::class . "\0" . 'typed_class_strings' => 'typed_class_strings',
        "\0" . self::class . "\0" . 'literal_int_types' => 'literal_int_types',
        "\0" . self::class . "\0" . 'literal_float_types' => 'literal_float_types',
        'by_ref' => 'by_ref',
        'reference_free' => 'reference_free',
        'allow_mutations' => 'allow_mutations',
        'has_mutations' => 'has_mutations',
        "\0" . self::class . "\0" . 'id' => 'id',
        "\0" . self::class . "\0" . 'exact_id' => 'exact_id',
        'parent_nodes' => 'parent_nodes',
        'propagate_parent_nodes' => 'propagate_parent_nodes',
        'different' => 'different',
    ];

    /**
     * Suppresses memory usage when unserializing objects.
     *
     * @see \Psalm\Storage\UnserializeMemoryUsageSuppressionTrait
     */
    public function __unserialize(array $properties): void
    {
        foreach (self::PROPERTY_KEYS_FOR_UNSERIALIZE as $key => $property_name) {
            /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
            $this->$property_name = $properties[$key];
        }
    }

    /**
     * @param TProperties $properties
     * @return static
     */
    public function setProperties(array $properties): self
    {
        $obj = null;
        foreach ($properties as $key => $value) {
            if ($this->{$key} !== $value) {
                if ($obj === null) {
                    $obj = clone $this;
                }
                /** @psalm-suppress ImpurePropertyAssignment We just cloned this object */
                $obj->{$key} = $value;
            }
        }
        return $obj ?? $this;
    }

    /**
     * @return static
     */
    public function setDifferent(bool $different): self
    {
        if ($different === $this->different) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->different = $different;
        return $cloned;
    }

    /**
     * @param array<string, DataFlowNode> $parent_nodes
     * @return static
     */
    public function setParentNodes(array $parent_nodes, bool $propagate_changes = false): self
    {
        if ($parent_nodes === $this->parent_nodes) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->parent_nodes = $parent_nodes;
        $cloned->propagate_parent_nodes = $propagate_changes;
        return $cloned;
    }


    /**
     * @param array<string, DataFlowNode> $parent_nodes
     * @return static
     */
    public function addParentNodes(array $parent_nodes): self
    {
        if (!$parent_nodes) {
            return $this;
        }
        $parent_nodes = $this->parent_nodes + $parent_nodes;
        if ($parent_nodes === $this->parent_nodes) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->parent_nodes = $parent_nodes;
        return $cloned;
    }

    /** @return static */
    public function setPossiblyUndefined(bool $possibly_undefined, ?bool $from_try = null): self
    {
        $from_try ??= $this->possibly_undefined_from_try;
        if ($this->possibly_undefined === $possibly_undefined
            && $this->possibly_undefined_from_try == $from_try
        ) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->possibly_undefined = $possibly_undefined;
        $cloned->possibly_undefined_from_try = $from_try;
        return $cloned;
    }

    /** @return static */
    public function setByRef(bool $by_ref)
    {
        if ($by_ref === $this->by_ref) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->by_ref = $by_ref;
        return $cloned;
    }

    /**
     * @psalm-mutation-free
     * @param non-empty-array<Atomic>  $types
     */
    public function setTypes(array $types): self
    {
        if ($types === $this->types) {
            return $this;
        }
        return $this->getBuilder()->setTypes($types)->freeze();
    }

    /**
     * @psalm-mutation-free
     */
    public function getBuilder(): MutableUnion
    {
        /** @psalm-suppress InvalidArgument It's actually filtered internally */
        return new MutableUnion($this->getAtomicTypes(), get_object_vars($this));
    }

    /**
     * @psalm-mutation-free
     */
    public function setFromDocblock(bool $fromDocblock = true): self
    {
        $cloned = clone $this;
        /** @psalm-suppress ImpureMethodCall Acting on clone */
        (new FromDocblockSetter($fromDocblock))->traverse($cloned);
        return $cloned;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    public static function visitMutable(MutableTypeVisitor $visitor, &$node, bool $cloned): bool
    {
        $result = true;
        $changed = false;
        $types = $node->types;
        foreach ($types as &$type) {
            $type_orig = $type;
            $result = $visitor->traverse($type);
            $changed = $changed || $type_orig !== $type;
            if (!$result) {
                break;
            }
        }
        unset($type);

        if ($changed) {
            $node = $node->setTypes($types);
        }

        return $result;
    }
}
