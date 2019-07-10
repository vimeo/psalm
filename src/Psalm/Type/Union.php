<?php
namespace Psalm\Type;

use function array_filter;
use function array_merge;
use function array_shift;
use function array_values;
use function count;
use function get_class;
use function is_string;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Type\TypeCombination;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use function reset;
use function strpos;
use function substr;

class Union
{
    /**
     * @var array<string, Atomic>
     */
    private $types = [];

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
    protected $checked = false;

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
     * @var array<string, TLiteralString>
     */
    private $literal_string_types = [];

    /**
     * @var array<string, Type\Atomic\TClassString>
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
     * Whether or not the type was passed by reference
     *
     * @var bool
     */
    public $by_ref = false;

    /** @var null|string */
    private $id;

    /**
     * Constructs an Union instance
     *
     * @param array<int, Atomic>     $types
     */
    public function __construct(array $types)
    {
        $from_docblock = false;

        if (!$types) {
            throw new \UnexpectedValueException('Cannot construct a union with empty types');
        }

        foreach ($types as $type) {
            $key = $type->getKey();
            $this->types[$key] = $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof Type\Atomic\TClassString && $type->as_type) {
                $this->typed_class_strings[$key] = $type;
            }

            $from_docblock = $from_docblock || $type->from_docblock;
        }

        $this->from_docblock = $from_docblock;
    }

    /**
     * @return array<string, Atomic>
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return void
     */
    public function addType(Atomic $type)
    {
        $this->types[$type->getKey()] = $type;

        if ($type instanceof TLiteralString) {
            $this->literal_string_types[$type->getKey()] = $type;
        } elseif ($type instanceof TLiteralInt) {
            $this->literal_int_types[$type->getKey()] = $type;
        } elseif ($type instanceof TLiteralFloat) {
            $this->literal_float_types[$type->getKey()] = $type;
        } elseif ($type instanceof TString && $this->literal_string_types) {
            foreach ($this->literal_string_types as $key => $_) {
                unset($this->literal_string_types[$key], $this->types[$key]);
            }
            if (!$type instanceof Type\Atomic\TClassString
                || !$type->as_type
            ) {
                foreach ($this->typed_class_strings as $key => $_) {
                    unset($this->typed_class_strings[$key], $this->types[$key]);
                }
            }
        } elseif ($type instanceof TInt && $this->literal_int_types) {
            foreach ($this->literal_int_types as $key => $_) {
                unset($this->literal_int_types[$key], $this->types[$key]);
            }
        } elseif ($type instanceof TFloat && $this->literal_float_types) {
            foreach ($this->literal_float_types as $key => $_) {
                unset($this->literal_float_types[$key], $this->types[$key]);
            }
        }

        $this->id = null;
    }

    public function __clone()
    {
        $this->literal_string_types = [];
        $this->literal_int_types = [];
        $this->literal_float_types = [];
        $this->typed_class_strings = [];

        foreach ($this->types as $key => &$type) {
            $type = clone $type;

            if ($type instanceof TLiteralInt) {
                $this->literal_int_types[$key] = $type;
            } elseif ($type instanceof TLiteralString) {
                $this->literal_string_types[$key] = $type;
            } elseif ($type instanceof TLiteralFloat) {
                $this->literal_float_types[$key] = $type;
            } elseif ($type instanceof Type\Atomic\TClassString && $type->as_type) {
                $this->typed_class_strings[$key] = $type;
            }
        }
    }

    public function __toString()
    {
        if (empty($this->types)) {
            return '';
        }
        $s = '';

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $printed_int = true;
            }

            $s .= $type . '|';
        }

        return substr($s, 0, -1) ?: '';
    }

    public function getKey() : string
    {
        if (empty($this->types)) {
            return '';
        }
        $s = '';

        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        foreach ($this->types as $type) {
            if ($type instanceof TLiteralFloat) {
                if ($printed_float) {
                    continue;
                }

                $s .= 'float|';
                $printed_float = true;
            } elseif ($type instanceof TLiteralString) {
                if ($printed_string) {
                    continue;
                }

                $s .= 'string|';
                $printed_string = true;
            } elseif ($type instanceof TLiteralInt) {
                if ($printed_int) {
                    continue;
                }

                $s .= 'int|';
                $printed_int = true;
            } else {
                $s .= $type->getKey() . '|';
            }
        }

        return substr($s, 0, -1) ?: '';
    }

    /**
     * @return string
     */
    public function getId()
    {
        if ($this->id) {
            return $this->id;
        }

        $s = '';
        foreach ($this->types as $type) {
            $s .= $type->getId() . '|';
        }

        $id = substr($s, 0, -1);

        $this->id = $id;

        return $id;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        foreach ($this->types as $type) {
            return $type->getAssertionString();
        }

        throw new \UnexpectedValueException('Should only be one type per assertion');
    }

    /**
     * @param  array<string, string> $aliased_classes
     *
     * @return string
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) {
        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        $s = '';

        foreach ($this->types as $type) {
            $type_string = $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format);

            if ($type instanceof TLiteralFloat && $type_string === 'float') {
                if ($printed_float) {
                    continue;
                }

                $printed_float = true;
            } elseif ($type instanceof TLiteralString && $type_string === 'string') {
                if ($printed_string) {
                    continue;
                }

                $printed_string = true;
            } elseif ($type instanceof TLiteralInt && $type_string === 'int') {
                if ($printed_int) {
                    continue;
                }

                $printed_int = true;
            }

            $s .= $type_string . '|';
        }

        return substr($s, 0, -1) ?: '';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string, string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null|string
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        $nullable = false;

        if (!$this->isSingleAndMaybeNullable()
            || $php_major_version < 7
            || (isset($this->types['null']) && $php_minor_version < 1)
        ) {
            return null;
        }

        $types = $this->types;

        if (isset($types['null'])) {
            unset($types['null']);

            $nullable = true;
        }

        if (!$types) {
            return null;
        }

        $atomic_type = array_values($types)[0];

        $atomic_type_string = $atomic_type->toPhpString(
            $namespace,
            $aliased_classes,
            $this_class,
            $php_major_version,
            $php_minor_version
        );

        if ($atomic_type_string) {
            return ($nullable ? '?' : '') . $atomic_type_string;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        if (!$this->isSingleAndMaybeNullable()) {
            return false;
        }

        $types = $this->types;

        if (isset($types['null'])) {
            unset($types['null']);
        }

        if (!$types) {
            return false;
        }

        $atomic_type = array_values($types)[0];

        return $atomic_type->canBeFullyExpressedInPhp();
    }

    /**
     * @return void
     */
    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->types as $type) {
            $type->setFromDocblock();
        }
    }

    /**
     * @param  string $type_string
     *
     * @return bool
     */
    public function removeType($type_string)
    {
        if (isset($this->types[$type_string])) {
            unset($this->types[$type_string]);

            if (strpos($type_string, '(')) {
                unset(
                    $this->literal_string_types[$type_string],
                    $this->literal_int_types[$type_string],
                    $this->literal_float_types[$type_string]
                );
            }

            $this->id = null;

            return true;
        }

        if ($type_string === 'string') {
            if ($this->literal_string_types) {
                foreach ($this->literal_string_types as $literal_key => $_) {
                    unset($this->types[$literal_key]);
                }
                $this->literal_string_types = [];
            }

            if ($this->typed_class_strings) {
                foreach ($this->typed_class_strings as $typed_class_key => $_) {
                    unset($this->types[$typed_class_key]);
                }
                $this->typed_class_strings = [];
            }

            unset($this->types['class-string'], $this->types['trait-string']);
        } elseif ($type_string === 'int' && $this->literal_int_types) {
            foreach ($this->literal_int_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_int_types = [];
        } elseif ($type_string === 'float' && $this->literal_float_types) {
            foreach ($this->literal_float_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_float_types = [];
        }

        return false;
    }

    /**
     * @return void
     */
    public function bustCache()
    {
        $this->id = null;
    }

    /**
     * @param  string  $type_string
     *
     * @return bool
     */
    public function hasType($type_string)
    {
        return isset($this->types[$type_string]);
    }

    /**
     * @return bool
     */
    public function hasArray()
    {
        return isset($this->types['array']);
    }

    /**
     * @return bool
     */
    public function hasEmptyArray()
    {
        return isset($this->types['array'])
            && $this->types['array'] instanceof Atomic\TArray
            && $this->types['array']->type_params[1]->isEmpty();
    }

    /**
     * @return bool
     */
    public function hasCallableType()
    {
        return isset($this->types['callable']) || isset($this->types['Closure']);
    }

    /**
     * @return bool
     */
    public function hasObject()
    {
        return isset($this->types['object']);
    }

    /**
     * @return bool
     */
    public function hasObjectType()
    {
        foreach ($this->types as $type) {
            if ($type->isObjectType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isObjectType()
    {
        foreach ($this->types as $type) {
            if (!$type->isObjectType()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasNamedObject()
    {
        foreach ($this->types as $type) {
            if ($type instanceof TNamedObject) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return isset($this->types['null']);
    }

    /**
     * @return bool
     */
    public function isFalsable()
    {
        return isset($this->types['false']);
    }

    /**
     * @return bool
     */
    public function hasBool()
    {
        return isset($this->types['bool']) || isset($this->types['false']) || isset($this->types['true']);
    }

    /**
     * @return bool
     */
    public function hasString()
    {
        return isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['numeric-string'])
            || isset($this->types['array-key'])
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    /**
     * @return bool
     */
    public function hasLiteralClassString()
    {
        return count($this->typed_class_strings) > 0;
    }

    /**
     * @return bool
     */
    public function hasInt()
    {
        return isset($this->types['int']) || isset($this->types['array-key']) || $this->literal_int_types;
    }

    /**
     * @return bool
     */
    public function hasArrayKey()
    {
        return isset($this->types['array-key']);
    }

    /**
     * @return bool
     */
    public function hasFloat()
    {
        return isset($this->types['float']) || $this->literal_float_types;
    }

    /**
     * @return bool
     */
    public function hasDefinitelyNumericType(bool $include_literal_int = true)
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['numeric-string'])
            || ($include_literal_int && $this->literal_int_types)
            || $this->literal_float_types;
    }

    /**
     * @return bool
     */
    public function hasPossiblyNumericType()
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['numeric-string'])
            || $this->literal_int_types
            || $this->literal_float_types
            || $this->literal_string_types;
    }

    /**
     * @return bool
     */
    public function hasScalar()
    {
        return isset($this->types['scalar']);
    }

    /**
     * @return bool
     */
    public function hasNumeric()
    {
        return isset($this->types['numeric']);
    }

    /**
     * @return bool
     */
    public function hasScalarType()
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['bool'])
            || isset($this->types['false'])
            || isset($this->types['true'])
            || isset($this->types['numeric'])
            || isset($this->types['numeric-string'])
            || $this->literal_int_types
            || $this->literal_float_types
            || $this->literal_string_types
            || $this->typed_class_strings;
    }

    /**
     * @return bool
     */
    public function hasTemplate()
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type) : bool {
                return $type instanceof Type\Atomic\TTemplateParam;
            }
        );
    }

    /**
     * @return bool
     */
    public function hasMixed()
    {
        return isset($this->types['mixed']);
    }

    /**
     * @return bool
     */
    public function isMixed()
    {
        return isset($this->types['mixed']) && count($this->types) === 1;
    }

    /**
     * @return bool
     */
    public function isEmptyMixed()
    {
        return isset($this->types['mixed'])
            && $this->types['mixed'] instanceof Type\Atomic\TEmptyMixed;
    }

    /**
     * @return bool
     */
    public function isVanillaMixed()
    {
        /**
         * @psalm-suppress UndefinedPropertyFetch
         */
        return isset($this->types['mixed'])
            && !$this->types['mixed']->from_loop_isset
            && get_class($this->types['mixed']) === Type\Atomic\TMixed::class;
    }

    /**
     * @return bool
     */
    public function isArrayKey()
    {
        return isset($this->types['array-key']) && count($this->types) === 1;
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return count($this->types) === 1 && isset($this->types['null']);
    }

    /**
     * @return bool
     */
    public function isFalse()
    {
        return count($this->types) === 1 && isset($this->types['false']);
    }

    /**
     * @return bool
     */
    public function isVoid()
    {
        return isset($this->types['void']);
    }

    /**
     * @return bool
     */
    public function isNever()
    {
        return isset($this->types['never-return']);
    }

    /**
     * @return bool
     */
    public function isGenerator()
    {
        return count($this->types) === 1
            && (($single_type = reset($this->types)) instanceof TNamedObject)
            && ($single_type->value === 'Generator');
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return isset($this->types['empty']);
    }

    /**
     * @return void
     */
    public function substitute(Union $old_type, Union $new_type = null)
    {
        if ($this->hasMixed() && !$this->isEmptyMixed()) {
            return;
        }

        if ($new_type && $new_type->ignore_nullable_issues) {
            $this->ignore_nullable_issues = true;
        }

        if ($new_type && $new_type->ignore_falsable_issues) {
            $this->ignore_falsable_issues = true;
        }

        foreach ($old_type->types as $old_type_part) {
            if (!$this->removeType($old_type_part->getKey())) {
                if ($old_type_part instanceof Type\Atomic\TFalse
                    && isset($this->types['bool'])
                    && !isset($this->types['true'])
                ) {
                    $this->removeType('bool');
                    $this->types['true'] = new Type\Atomic\TTrue;
                } elseif ($old_type_part instanceof Type\Atomic\TTrue
                    && isset($this->types['bool'])
                    && !isset($this->types['false'])
                ) {
                    $this->removeType('bool');
                    $this->types['false'] = new Type\Atomic\TFalse;
                } elseif (isset($this->types['iterable'])) {
                    if ($old_type_part instanceof Type\Atomic\TNamedObject
                        && $old_type_part->value === 'Traversable'
                        && !isset($this->types['array'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['array'] = new Type\Atomic\TArray([Type::getArrayKey(), Type::getMixed()]);
                    }

                    if ($old_type_part instanceof Type\Atomic\TArray
                        && !isset($this->types['traversable'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['traversable'] = new Type\Atomic\TNamedObject('Traversable');
                    }
                } elseif (isset($this->types['array-key'])) {
                    if ($old_type_part instanceof Type\Atomic\TString
                        && !isset($this->types['int'])
                    ) {
                        $this->removeType('array-key');
                        $this->types['int'] = new Type\Atomic\TInt();
                    }

                    if ($old_type_part instanceof Type\Atomic\TInt
                        && !isset($this->types['string'])
                    ) {
                        $this->removeType('array-key');
                        $this->types['string'] = new Type\Atomic\TString();
                    }
                }
            }
        }

        if ($new_type) {
            foreach ($new_type->types as $key => $new_type_part) {
                if (!isset($this->types[$key])
                    || ($new_type_part instanceof Type\Atomic\Scalar
                        && get_class($new_type_part) === get_class($this->types[$key]))
                ) {
                    $this->types[$key] = $new_type_part;
                } else {
                    $combined = TypeCombination::combineTypes([$new_type_part, $this->types[$key]]);
                    $this->types[$key] = array_values($combined->types)[0];
                }
            }
        } elseif (count($this->types) === 0) {
            $this->types['mixed'] = new Atomic\TMixed();
        }

        $this->id = null;
    }

    /**
     * @param  array<string, array<string, array{Type\Union}>> $template_types
     * @param  array<string, array<string, array{Type\Union, 1?: int}>> $generic_params
     * @param  Type\Union|null      $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array &$template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Type\Union $input_type = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) {
        $keys_to_unset = [];

        foreach ($this->types as $key => $atomic_type) {
            $original_key = $key;

            if ($bracket_pos = strpos($key, '<')) {
                $key = substr($key, 0, $bracket_pos);
            }

            if ($atomic_type instanceof Type\Atomic\TTemplateParam
                && isset($template_types[$key][$atomic_type->defining_class ?: ''])
            ) {
                $template_type = $template_types[$key][$atomic_type->defining_class ?: ''][0];

                if ($template_type->getId() !== $key) {
                    $replacement_type = $template_type;

                    if ($replace) {
                        if ($replacement_type->hasMixed()
                            && !$atomic_type->as->hasMixed()
                        ) {
                            foreach ($atomic_type->as->getTypes() as $as_atomic_type) {
                                $this->types[$as_atomic_type->getKey()] = clone $as_atomic_type;
                            }
                        } else {
                            foreach ($replacement_type->getTypes() as $replacement_atomic_type) {
                                $replacements_found = false;

                                // @codingStandardsIgnoreStart
                                if ($replacement_atomic_type instanceof Type\Atomic\TTemplateKeyOf
                                    && isset($template_types[$replacement_atomic_type->param_name][$replacement_atomic_type->defining_class ?: ''][0])
                                ) {
                                    $keyed_template = $template_types[$replacement_atomic_type->param_name][$replacement_atomic_type->defining_class ?: ''][0];

                                    if ($keyed_template->isSingle()) {
                                        $keyed_template = array_values($keyed_template->getTypes())[0];
                                    }

                                    if ($keyed_template instanceof Type\Atomic\ObjectLike
                                        || $keyed_template instanceof Type\Atomic\TArray
                                    ) {
                                        if ($keyed_template instanceof Type\Atomic\ObjectLike) {
                                            $key_type = $keyed_template->getGenericKeyType();
                                        } else {
                                            $key_type = $keyed_template->type_params[0];
                                        }

                                        $replacements_found = true;

                                        foreach ($key_type->getTypes() as $key_type_atomic) {
                                            $this->types[$key_type_atomic->getKey()] = clone $key_type_atomic;
                                        }

                                        $generic_params[$key][$atomic_type->defining_class ?: ''][0]
                                            = clone $key_type;
                                    }
                                }

                                if ($replacement_atomic_type instanceof Type\Atomic\TTemplateParam) {
                                    foreach ($replacement_atomic_type->as->getTypes() as $nested_type_atomic) {
                                        $this->types[$nested_type_atomic->getKey()] = clone $nested_type_atomic;
                                    }
                                }
                                // @codingStandardsIgnoreEnd

                                if (!$replacements_found) {
                                    $this->types[$replacement_atomic_type->getKey()] = clone $replacement_atomic_type;
                                }
                            }

                            foreach ($replacement_type->getTypes() as $replacement_key => $_) {
                                if ($replacement_key !== $key) {
                                    $keys_to_unset[] = $original_key;
                                }
                            }
                        }

                        $this->had_template = true;

                        if ($input_type
                            && (
                                $atomic_type->as->isMixed()
                                || !$codebase
                                || TypeAnalyzer::isContainedBy(
                                    $codebase,
                                    $input_type,
                                    $atomic_type->as
                                )
                            )
                        ) {
                            $generic_param = clone $input_type;

                            if ($this->isNullable() && $generic_param->isNullable() && !$generic_param->isNull()) {
                                $generic_param->removeType('null');
                            }

                            $generic_param->setFromDocblock();

                            if (isset($generic_params[$key][$atomic_type->defining_class ?: ''][0])) {
                                $existing_depth = $generic_params[$key][$atomic_type->defining_class ?: ''][1] ?? -1;

                                if ($existing_depth > $depth) {
                                    continue;
                                }

                                if ($existing_depth === $depth) {
                                    $generic_param = Type::combineUnionTypes(
                                        $generic_params[$key][$atomic_type->defining_class ?: ''][0],
                                        $generic_param,
                                        $codebase
                                    );
                                }
                            }

                            $generic_params[$key][$atomic_type->defining_class ?: ''] = [
                                $generic_param,
                                $depth,
                            ];
                        }
                    } elseif ($add_upper_bound && $input_type) {
                        if ($codebase
                            && TypeAnalyzer::isContainedBy(
                                $codebase,
                                $input_type,
                                $replacement_type
                            )
                        ) {
                            $template_types[$key][$atomic_type->defining_class ?: ''][0] = clone $input_type;
                        }
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateParamClass
                && isset($template_types[$atomic_type->param_name])
            ) {
                if ($replace) {
                    $was_single = $this->isSingle();

                    $class_string = new Type\Atomic\TClassString($atomic_type->as, $atomic_type->as_type);

                    $keys_to_unset[] = $original_key;

                    $this->types[$class_string->getKey()] = $class_string;

                    if ($input_type) {
                        $valid_input_atomic_types = [];

                        foreach ($input_type->getTypes() as $input_atomic_type) {
                            if ($input_atomic_type instanceof Type\Atomic\TLiteralClassString) {
                                $valid_input_atomic_types[] = new Type\Atomic\TNamedObject(
                                    $input_atomic_type->value
                                );
                            } elseif ($input_atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                                $valid_input_atomic_types[] = new Type\Atomic\TTemplateParam(
                                    $input_atomic_type->param_name,
                                    $input_atomic_type->as_type
                                        ? new Union([$input_atomic_type->as_type])
                                        : ($input_atomic_type->as === 'object'
                                            ? Type::getObject()
                                            : Type::getMixed())
                                );
                            } elseif ($input_atomic_type instanceof Type\Atomic\TClassString) {
                                if ($input_atomic_type->as_type) {
                                    $valid_input_atomic_types[] = clone $input_atomic_type->as_type;
                                } elseif ($input_atomic_type->as !== 'object') {
                                    $valid_input_atomic_types[] = new Type\Atomic\TNamedObject(
                                        $input_atomic_type->as
                                    );
                                } else {
                                    $valid_input_atomic_types[] = new Type\Atomic\TObject();
                                }
                            }
                        }

                        if ($valid_input_atomic_types) {
                            $generic_param = new Union($valid_input_atomic_types);
                            $generic_param->setFromDocblock();

                            $generic_params[$atomic_type->param_name][$atomic_type->defining_class ?: ''] = [
                                $generic_param,
                                $depth,
                            ];
                        } elseif ($was_single) {
                            $generic_params[$atomic_type->param_name][$atomic_type->defining_class ?: ''] = [
                                Type::getMixed(),
                                $depth,
                            ];
                        }
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateIndexedAccess) {
                if ($replace) {
                    if (isset($template_types[$atomic_type->array_param_name][$atomic_type->defining_class ?: ''])
                        && isset($generic_params[$atomic_type->offset_param_name][''])
                    ) {
                        $array_template_type
                            = $template_types[$atomic_type->array_param_name][$atomic_type->defining_class ?: ''][0];
                        $offset_template_type
                            = $generic_params[$atomic_type->offset_param_name][''][0];

                        if ($array_template_type->isSingle()
                            && $offset_template_type->isSingle()
                            && !$array_template_type->isMixed()
                            && !$offset_template_type->isMixed()
                        ) {
                            $array_template_type = array_values($array_template_type->types)[0];
                            $offset_template_type = array_values($offset_template_type->types)[0];

                            if ($array_template_type instanceof Type\Atomic\ObjectLike
                                && ($offset_template_type instanceof Type\Atomic\TLiteralString
                                    || $offset_template_type instanceof Type\Atomic\TLiteralInt)
                                && isset($array_template_type->properties[$offset_template_type->value])
                            ) {
                                $replacement_type
                                    = clone $array_template_type->properties[$offset_template_type->value];

                                $keys_to_unset[] = $original_key;

                                foreach ($replacement_type->getTypes() as $replacement_atomic_type) {
                                    $this->types[$replacement_atomic_type->getKey()] = $replacement_atomic_type;
                                }
                            }
                        }
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateKeyOf) {
                if ($replace) {
                    if (isset($template_types[$atomic_type->param_name][$atomic_type->defining_class ?: ''])) {
                        $template_type
                            = $template_types[$atomic_type->param_name][$atomic_type->defining_class ?: ''][0];

                        if ($template_type->isSingle()) {
                            $template_type = array_values($template_type->types)[0];

                            if ($template_type instanceof Type\Atomic\ObjectLike
                                || $template_type instanceof Type\Atomic\TArray
                            ) {
                                if ($template_type instanceof Type\Atomic\ObjectLike) {
                                    $key_type = $template_type->getGenericKeyType();
                                } else {
                                    $key_type = clone $template_type->type_params[0];
                                }

                                $keys_to_unset[] = $original_key;

                                foreach ($key_type->getTypes() as $key_atomic_type) {
                                    $this->types[$key_atomic_type->getKey()] = $key_atomic_type;
                                }
                            }
                        }
                    }
                }
            } else {
                $matching_atomic_type = null;

                if ($input_type && $codebase && !$input_type->hasMixed()) {
                    foreach ($input_type->types as $input_key => $atomic_input_type) {
                        if ($bracket_pos = strpos($input_key, '<')) {
                            $input_key = substr($input_key, 0, $bracket_pos);
                        }

                        if ($input_key === $key) {
                            $matching_atomic_type = $atomic_input_type;
                            break;
                        }

                        if ($input_key === 'Closure' && $key === 'callable') {
                            $matching_atomic_type = $atomic_input_type;
                            break;
                        }

                        if (($atomic_input_type instanceof Type\Atomic\TArray
                                || $atomic_input_type instanceof Type\Atomic\ObjectLike)
                            && $key === 'iterable'
                        ) {
                            $matching_atomic_type = $atomic_input_type;
                            break;
                        }

                        if (strpos($input_key, $key . '&') === 0) {
                            $matching_atomic_type = $atomic_input_type;
                            break;
                        }

                        if ($key === 'callable') {
                            $matching_atomic_type = TypeAnalyzer::getCallableFromAtomic(
                                $codebase,
                                $atomic_input_type
                            );

                            if ($matching_atomic_type) {
                                break;
                            }
                        }

                        if ($atomic_input_type instanceof TNamedObject && $atomic_type instanceof TNamedObject) {
                            try {
                                $classlike_storage =
                                    $codebase->classlike_storage_provider->get($atomic_input_type->value);

                                if ($atomic_input_type instanceof TGenericObject
                                    && isset($classlike_storage->template_type_extends[$atomic_type->value])
                                ) {
                                    $matching_atomic_type = $atomic_input_type;
                                    break;
                                }

                                if (isset($classlike_storage->template_type_extends[$atomic_type->value])) {
                                    $extends_list = $classlike_storage->template_type_extends[$atomic_type->value];

                                    $new_generic_params = [];

                                    foreach ($extends_list as $extends_key => $value) {
                                        if (is_string($extends_key)) {
                                            $new_generic_params[] = $value;
                                        }
                                    }

                                    $matching_atomic_type = new TGenericObject(
                                        $atomic_input_type->value,
                                        $new_generic_params
                                    );
                                    break;
                                }
                            } catch (\InvalidArgumentException $e) {
                                // do nothing
                            }
                        }
                    }
                }

                $atomic_type->replaceTemplateTypesWithStandins(
                    $template_types,
                    $generic_params,
                    $codebase,
                    $matching_atomic_type,
                    $replace,
                    $add_upper_bound,
                    $depth + 1
                );
            }
        }

        if ($replace) {
            foreach ($keys_to_unset as $key) {
                unset($this->types[$key]);
            }

            if (!$this->types) {
                throw new \UnexpectedValueException('Cannot remove all keys');
            }

            $this->id = null;
        }
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, Codebase $codebase = null)
    {
        $keys_to_unset = [];

        $new_types = [];

        $is_mixed = false;

        foreach ($this->types as $key => $atomic_type) {
            $atomic_type->replaceTemplateTypesWithArgTypes($template_types, $codebase);

            if ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                $template_type = null;

                if (isset($template_types[$atomic_type->param_name][$atomic_type->defining_class ?: ''])) {
                    $template_type = $template_types[$atomic_type->param_name][$atomic_type->defining_class ?: ''][0];

                    if (!$atomic_type->as->isMixed() && $template_type->isMixed()) {
                        $template_type = clone $atomic_type->as;
                    } else {
                        $template_type = clone $template_type;
                    }

                    if ($atomic_type->extra_types) {
                        foreach ($template_type->getTypes() as $template_type_key => $atomic_template_type) {
                            if ($atomic_template_type instanceof TNamedObject
                                || $atomic_template_type instanceof TTemplateParam
                                || $atomic_template_type instanceof TIterable
                                || $atomic_template_type instanceof Type\Atomic\TObjectWithProperties
                            ) {
                                $atomic_template_type->extra_types = $atomic_type->extra_types;
                            } elseif ($atomic_template_type instanceof Type\Atomic\TObject) {
                                $first_atomic_type = array_shift($atomic_type->extra_types);

                                if ($atomic_type->extra_types) {
                                    $first_atomic_type->extra_types = $atomic_type->extra_types;
                                }

                                $template_type->removeType($template_type_key);
                                $template_type->addType($first_atomic_type);
                            }
                        }
                    }
                } elseif ($codebase && $atomic_type->defining_class) {
                    foreach ($template_types as $template_type_map) {
                        foreach ($template_type_map as $template_class => $_) {
                            if (!$template_class) {
                                continue;
                            }

                            try {
                                $classlike_storage = $codebase->classlike_storage_provider->get($template_class);

                                if ($classlike_storage->template_type_extends) {
                                    $defining_class = $atomic_type->defining_class;

                                    if (isset($classlike_storage->template_type_extends[$defining_class])) {
                                        $param_map = $classlike_storage->template_type_extends[$defining_class];

                                        if (isset($param_map[$key])
                                            && isset($template_types[(string) $param_map[$key]][$template_class])
                                        ) {
                                            $template_type
                                                = clone $template_types[(string) $param_map[$key]][$template_class][0];
                                        }
                                    }
                                }
                            } catch (\InvalidArgumentException $e) {
                            }
                        }
                    }
                }

                if ($template_type) {
                    $keys_to_unset[] = $key;

                    foreach ($template_type->types as $template_type_part) {
                        if ($template_type_part instanceof Type\Atomic\TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateParamClass) {
                $template_type = isset($template_types[$atomic_type->param_name][$atomic_type->defining_class ?: ''])
                    ? clone $template_types[$atomic_type->param_name][$atomic_type->defining_class ?: ''][0]
                    : Type::getMixed();

                foreach ($template_type->types as $template_type_part) {
                    if ($template_type_part instanceof Type\Atomic\TMixed
                        || $template_type_part instanceof Type\Atomic\TObject
                    ) {
                        $unknown_class_string = new Type\Atomic\TClassString();

                        $new_types[$unknown_class_string->getKey()] = $unknown_class_string;
                        $keys_to_unset[] = $key;
                    } elseif ($template_type_part instanceof Type\Atomic\TNamedObject) {
                        $literal_class_string = new Type\Atomic\TClassString(
                            $template_type_part->value,
                            $template_type_part
                        );

                        $new_types[$literal_class_string->getKey()] = $literal_class_string;
                        $keys_to_unset[] = $key;
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TTemplateIndexedAccess) {
                $keys_to_unset[] = $key;

                $template_type = null;

                if (isset($template_types[$atomic_type->array_param_name][$atomic_type->defining_class ?: ''])
                    && isset($template_types[$atomic_type->offset_param_name][''])
                ) {
                    $array_template_type
                        = $template_types[$atomic_type->array_param_name][$atomic_type->defining_class ?: ''][0];
                    $offset_template_type
                        = $template_types[$atomic_type->offset_param_name][''][0];

                    if ($array_template_type->isSingle()
                        && $offset_template_type->isSingle()
                        && !$array_template_type->isMixed()
                        && !$offset_template_type->isMixed()
                    ) {
                        $array_template_type = array_values($array_template_type->types)[0];
                        $offset_template_type = array_values($offset_template_type->types)[0];

                        if ($array_template_type instanceof Type\Atomic\ObjectLike
                            && ($offset_template_type instanceof Type\Atomic\TLiteralString
                                || $offset_template_type instanceof Type\Atomic\TLiteralInt)
                            && isset($array_template_type->properties[$offset_template_type->value])
                        ) {
                            $template_type = clone $array_template_type->properties[$offset_template_type->value];
                        }
                    }
                }

                if ($template_type) {
                    foreach ($template_type->types as $template_type_part) {
                        if ($template_type_part instanceof Type\Atomic\TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                } else {
                    $new_types[$key] = new Type\Atomic\TMixed();
                }
            }
        }

        $this->id = null;

        if ($is_mixed) {
            $this->types = $new_types;

            return;
        }

        foreach ($keys_to_unset as $key) {
            unset($this->types[$key]);
        }

        $this->types = array_merge($this->types, $new_types);
    }

    /**
     * @return bool
     */
    public function isSingle()
    {
        $type_count = count($this->types);

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return $type_count === 1;
    }

    /**
     * @return bool
     */
    public function isSingleAndMaybeNullable()
    {
        $is_nullable = isset($this->types['null']);

        $type_count = count($this->types);

        if ($type_count === 1 && $is_nullable) {
            return false;
        }

        $int_literal_count = count($this->literal_int_types);
        $string_literal_count = count($this->literal_string_types);
        $float_literal_count = count($this->literal_float_types);

        if (($int_literal_count && $string_literal_count)
            || ($int_literal_count && $float_literal_count)
            || ($string_literal_count && $float_literal_count)
        ) {
            return false;
        }

        if ($int_literal_count || $string_literal_count || $float_literal_count) {
            $type_count -= $int_literal_count + $string_literal_count + $float_literal_count - 1;
        }

        return ($type_count - (int) $is_nullable) === 1;
    }

    /**
     * @return bool true if this is an int
     */
    public function isInt()
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['int']) || $this->literal_int_types;
    }

    /**
     * @return bool true if this is a float
     */
    public function isFloat()
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['float']) || $this->literal_float_types;
    }

    /**
     * @return bool true if this is a string
     */
    public function isString()
    {
        if (!$this->isSingle()) {
            return false;
        }

        return isset($this->types['string'])
            || isset($this->types['class-string'])
            || isset($this->types['trait-string'])
            || isset($this->types['numeric-string'])
            || $this->literal_string_types;
    }

    /**
     * @return bool true if this is a string literal with only one possible value
     */
    public function isSingleStringLiteral()
    {
        return count($this->types) === 1 && count($this->literal_string_types) === 1;
    }

    /**
     * @throws \InvalidArgumentException if isSingleStringLiteral is false
     *
     * @return TLiteralString the only string literal represented by this union type
     */
    public function getSingleStringLiteral()
    {
        if (count($this->types) !== 1 || count($this->literal_string_types) !== 1) {
            throw new \InvalidArgumentException('Not a string literal');
        }

        return reset($this->literal_string_types);
    }

    public function allStringLiterals() : bool
    {
        foreach ($this->types as $atomic_key_type) {
            if (!$atomic_key_type instanceof TLiteralString) {
                return false;
            }
        }

        return true;
    }

    public function hasLiteralValue() : bool
    {
        return $this->literal_int_types
            || $this->literal_string_types
            || $this->literal_float_types
            || isset($this->types['false'])
            || isset($this->types['true']);
    }

    /**
     * @return bool true if this is a int literal with only one possible value
     */
    public function isSingleIntLiteral()
    {
        return count($this->types) === 1 && count($this->literal_int_types) === 1;
    }

    /**
     * @throws \InvalidArgumentException if isSingleIntLiteral is false
     *
     * @return TLiteralInt the only int literal represented by this union type
     */
    public function getSingleIntLiteral()
    {
        if (count($this->types) !== 1 || count($this->literal_int_types) !== 1) {
            throw new \InvalidArgumentException('Not an int literal');
        }

        return reset($this->literal_int_types);
    }

    public function hasSingleNamedObject() : bool
    {
        return $this->isSingle() && $this->hasNamedObject();
    }

    public function getSingleNamedObject() : TNamedObject
    {
        $first_value = array_values($this->types)[0];

        if (!$first_value instanceof TNamedObject) {
            throw new \UnexpectedValueException('Bad object');
        }

        return $first_value;
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return null|false
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        if ($this->checked) {
            return;
        }

        $all_good = true;

        foreach ($this->types as $atomic_type) {
            if ($atomic_type->check(
                $source,
                $code_location,
                $suppressed_issues,
                $phantom_classes,
                $inferred,
                $prevent_template_covariance
            ) === false) {
                $all_good = false;
            }
        }

        if (!$all_good) {
            return false;
        }
        $this->checked = true;
    }

    /**
     * @param  array<string, mixed> $phantom_classes
     *
     * @return void
     */
    public function queueClassLikesForScanning(
        Codebase $codebase,
        FileStorage $file_storage = null,
        array $phantom_classes = []
    ) {
        foreach ($this->types as $atomic_type) {
            $atomic_type->queueClassLikesForScanning(
                $codebase,
                $file_storage,
                $phantom_classes
            );
        }
    }

    public function containsClassLike(string $fq_class_like_name) : bool
    {
        foreach ($this->types as $atomic_type) {
            if ($atomic_type->containsClassLike($fq_class_like_name)) {
                return true;
            }
        }

        return false;
    }

    public function replaceClassLike(string $old, string $new) : void
    {
        foreach ($this->types as $key => $atomic_type) {
            $atomic_type->replaceClassLike($old, $new);

            $this->removeType($key);
            $this->addType($atomic_type);
        }
    }

    /**
     * @return bool
     */
    public function equals(Union $other_type)
    {
        if ($other_type === $this) {
            return true;
        }

        if ($other_type->id && $this->id && $other_type->id !== $this->id) {
            return false;
        }

        if ($this->possibly_undefined !== $other_type->possibly_undefined) {
            return false;
        }

        if ($this->had_template !== $other_type->had_template) {
            return false;
        }

        if ($this->possibly_undefined_from_try !== $other_type->possibly_undefined_from_try) {
            return false;
        }

        if ($this->from_calculation !== $other_type->from_calculation) {
            return false;
        }

        if ($this->initialized !== $other_type->initialized) {
            return false;
        }

        if ($this->from_docblock !== $other_type->from_docblock) {
            return false;
        }

        if (count($this->types) !== count($other_type->types)) {
            return false;
        }

        $other_atomic_types = $other_type->types;

        foreach ($this->types as $key => $atomic_type) {
            if (!isset($other_atomic_types[$key])) {
                return false;
            }

            if (!$atomic_type->equals($other_atomic_types[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, TLiteralString>
     */
    public function getLiteralStrings()
    {
        return $this->literal_string_types;
    }

    /**
     * @return array<string, TLiteralInt>
     */
    public function getLiteralInts()
    {
        return $this->literal_int_types;
    }

    /**
     * @return array<string, TLiteralFloat>
     */
    public function getLiteralFloats()
    {
        return $this->literal_float_types;
    }
}
