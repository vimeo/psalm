<?php
namespace Psalm\Type;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Storage\FileStorage;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNull;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Atomic\TMixed;

/**
 * Should we have an annotation for "this can be used externally but it can't be extended externally"?
 */
abstract class TypeNode
{
    /**
     * If this type is negated (eg `!int` represents a type that cannot be `int`).
     *
     * @var bool
     * @readonly
     */
    public $negated = false;

    /**
     * Whether the type originated in a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * @var array<string, DataFlowNode>
     */
    public $parent_nodes = [];

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
    public $initialized_class = null;

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
     * Whether or not this type had a template, since replaced
     *
     * @var bool
     */
    public $had_template = false;

    /**
     * Whether or not this type comes from a template "as" default
     *
     * @var bool
     */
    public $from_template_default = false;

    /**
     * Whether or not the type was passed by reference
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
     * @return array<TypeNode>
     */
    abstract public function getChildNodes() : array;

    abstract public function getId(): string;

    /**
     * @psalm-mutation-free
     */
    abstract public function containedBy(TypeNode $other, ?Codebase $codebase = null): TypeComparisonResult2;

    /**
     * @psalm-mutation-free
     */
    abstract public function intersects(TypeNode $other, ?Codebase $codebase = null): TypeComparisonResult2;

    abstract public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string;

    /**
     * @psalm-mutation-free
     */
    abstract public function equals(TypeNode $other_type, bool $ensure_source_equality): bool;

    /**
     * @psalm-mutation-free
     */
    abstract public function hasObjectType(): bool;

    /**
     * @param  array<string, mixed> $phantom_classes
     *
     */
    public function queueClassLikesForScanning(
        Codebase $codebase,
        ?FileStorage $file_storage = null,
        array $phantom_classes = []
    ): void {
    }

    /**
     * @psalm-mutation-free
     */
    public function isNullable(bool $consider_mixed_nullable = false): bool
    {
        $codebase = ProjectAnalyzer::getInstance()->getCodebase();

        $type = $this;

        // if (!$consider_mixed_nullable && $this instanceof TMixed) { TODO after Union is rewritten
        if (!$consider_mixed_nullable && (new TMixed())->containedBy($this, $codebase)->result) {
            if (get_class($type) === TMixed::class) {
                return false;
            }
            if ($type instanceof Union) {
                $type = clone $type;
                $type->removeType("mixed");
            }
        }

        return (new TNull())->containedBy($type, $codebase)->result;
    }

    public function setFromDocblock(): void
    {
        $this->from_docblock = true;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasArray(): bool
    {
        return $this instanceof Atomic\TArray || $this instanceof Atomic\TList;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasTemplate(): bool
    {
        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasConditional(): bool
    {
        return false;
    }

    /**
     * @psalm-mutation-free
     */
    public function isSingle(): bool
    {
        return true;
    }

    /**
     * @psalm-mutation-free
     */
    public function hasFalse(): bool
    {
        return (new TFalse())->containedBy($this)->result;
    }

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes(): array
    {
        $template_type_collector = new \Psalm\Internal\TypeVisitor\TemplateTypeCollector();

        $template_type_collector->traverseArray([$this]);

        return $template_type_collector->getTemplateTypes();
    }
}
