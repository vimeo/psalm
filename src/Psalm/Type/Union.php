<?php

namespace Psalm\Type;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\TypeVisitor\FromDocblockSetter;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Stringable;

use function get_object_vars;

final class Union implements TypeNode, Stringable
{
    use UnionTrait;

    /**
     * @psalm-readonly
     * @var non-empty-array<string, Atomic>
     */
    private $types;

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
    private $literal_string_types = [];

    /**
     * @var array<string, TClassString>
     */
    private $typed_class_strings = [];

    /**
     * @var array<string, TLiteralInt>
     */
    private $literal_int_types = [];

    /**
     * @var array<string, TLiteralFloat>
     */
    private $literal_float_types = [];

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
     * @var null|string
     */
    private $id;

    /**
     * This is a cache of getId on exact mode
     * @var null|string
     */
    private $exact_id;


    /**
     * @var array<string, DataFlowNode>
     */
    public $parent_nodes = [];

    /**
     * @var bool
     */
    public $different = false;

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
        $union = new MutableUnion($this->getAtomicTypes());
        foreach (get_object_vars($this) as $key => $value) {
            if ($key === 'types') {
                continue;
            }
            if ($key === 'id') {
                continue;
            }
            if ($key === 'exact_id') {
                continue;
            }
            if ($key === 'literal_string_types') {
                continue;
            }
            if ($key === 'typed_class_strings') {
                continue;
            }
            if ($key === 'literal_int_types') {
                continue;
            }
            if ($key === 'literal_float_types') {
                continue;
            }
            $union->{$key} = $value;
        }
        return $union;
    }

    /**
     * @psalm-mutation-free
     */
    public function setFromDocblock(bool $fromDocblock = true): self
    {
        $cloned = clone $this;
        (new FromDocblockSetter($fromDocblock))->traverse($cloned);
        return $cloned;
    }
}
