<?php
namespace Psalm\Type;

use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TString;
use Psalm\Internal\Type\TypeCombination;

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
                unset($this->literal_string_types[$key]);
                unset($this->types[$key]);
            }
            if (!$type instanceof Type\Atomic\TClassString
                || !$type->as_type
            ) {
                foreach ($this->typed_class_strings as $key => $_) {
                    unset($this->typed_class_strings[$key]);
                    unset($this->types[$key]);
                }
            }
        } elseif ($type instanceof TInt && $this->literal_int_types) {
            foreach ($this->literal_int_types as $key => $_) {
                unset($this->literal_int_types[$key]);
                unset($this->types[$key]);
            }
        } elseif ($type instanceof TFloat && $this->literal_float_types) {
            foreach ($this->literal_float_types as $key => $_) {
                unset($this->literal_float_types[$key]);
                unset($this->types[$key]);
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
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        $printed_int = false;
        $printed_float = false;
        $printed_string = false;

        $s = '';

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

            $s .= $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format) . '|';
        }

        return substr($s, 0, -1) ?: '';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
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
                unset($this->literal_string_types[$type_string]);
                unset($this->literal_int_types[$type_string]);
                unset($this->literal_float_types[$type_string]);
            }

            $this->id = null;

            return true;
        } elseif ($type_string === 'string' && $this->literal_string_types) {
            foreach ($this->literal_string_types as $literal_key => $_) {
                unset($this->types[$literal_key]);
            }
            $this->literal_string_types = [];
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
            || isset($this->types['numeric-string'])
            || isset($this->types['array-key'])
            || $this->literal_string_types
            || $this->typed_class_strings;
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
    public function hasScalarType()
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['class-string'])
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
        return count($this->types) === 1 && isset($this->types['Generator']);
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
     * @param  array<string, array{Union, ?string}> $template_types
     * @param  array<string, array{Union, ?string}> $generic_params
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
        bool $add_upper_bound = false
    ) {
        $keys_to_unset = [];

        foreach ($this->types as $key => $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TGenericParam
                && isset($template_types[$key])
            ) {
                if ($template_types[$key][0]->getId() !== $key) {
                    $first_atomic_type = array_values($template_types[$key][0]->getTypes())[0];

                    if ($replace) {
                        $this->types[$first_atomic_type->getKey()] = clone $first_atomic_type;

                        if ($first_atomic_type->getKey() !== $key) {
                            $keys_to_unset[] = $key;
                        }

                        if ($input_type) {
                            $generic_param = clone $input_type;
                            $generic_param->setFromDocblock();

                            if (isset($generic_params[$key][0])) {
                                $generic_param = Type::combineUnionTypes(
                                    $generic_params[$key][0],
                                    $generic_param,
                                    $codebase
                                );
                            }

                            $generic_params[$key] = [
                                $generic_param,
                                $atomic_type->defining_class
                            ];
                        }
                    } elseif ($add_upper_bound && $input_type) {
                        if ($codebase
                            && TypeAnalyzer::isContainedBy(
                                $codebase,
                                $input_type,
                                $template_types[$key][0]
                            )
                        ) {
                            $template_types[$key][0] = clone $input_type;
                        }
                    }
                }
            } elseif ($atomic_type instanceof Type\Atomic\TGenericParamClass
                && isset($template_types[$atomic_type->param_name])
            ) {
                if ($replace) {
                    $class_string = new Type\Atomic\TClassString($atomic_type->as, $atomic_type->as_type);

                    $keys_to_unset[] = $key;

                    $this->types[$class_string->getKey()] = $class_string;

                    if ($input_type) {
                        $valid_input_atomic_types = [];

                        foreach ($input_type->getTypes() as $input_atomic_type) {
                            if ($input_atomic_type instanceof Type\Atomic\TLiteralClassString) {
                                $valid_input_atomic_types[] = new Type\Atomic\TNamedObject(
                                    $input_atomic_type->value
                                );
                            } elseif ($input_atomic_type instanceof Type\Atomic\TGenericParamClass) {
                                $valid_input_atomic_types[] = new Type\Atomic\TGenericParam(
                                    $input_atomic_type->param_name,
                                    $input_atomic_type->as_type
                                        ? new Union([$input_atomic_type->as_type])
                                        : Type::getMixed()
                                );
                            }
                        }

                        if ($valid_input_atomic_types) {
                            $generic_param = new Union($valid_input_atomic_types);
                            $generic_param->setFromDocblock();
                        } else {
                            $generic_param = Type::getMixed();
                        }

                        $generic_params[$atomic_type->param_name] = [
                            $generic_param,
                            $atomic_type->defining_class
                        ];
                    }
                }
            } else {
                $matching_atomic_type = null;

                if ($input_type && $codebase) {
                    foreach ($input_type->types as $input_key => $atomic_input_type) {
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
                                    && isset($classlike_storage->template_type_extends[strtolower($key)])
                                ) {
                                    $matching_atomic_type = $atomic_input_type;
                                    break;
                                }

                                if (isset($classlike_storage->template_type_extends[strtolower($key)])) {
                                    $extends_list = $classlike_storage->template_type_extends[strtolower($key)];

                                    $new_generic_params = [];

                                    foreach ($extends_list as $key => $value) {
                                        if (is_int($key)) {
                                            $new_generic_params[] = new Type\Union([$value]);
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
                    $add_upper_bound
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
     * @param  array<string, array{Union, ?string}>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, Codebase $codebase = null)
    {
        $keys_to_unset = [];

        $new_types = [];

        $is_mixed = false;

        foreach ($this->types as $key => $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TGenericParam) {
                $keys_to_unset[] = $key;

                $template_type = null;

                if (isset($template_types[$key]) && $atomic_type->defining_class === $template_types[$key][1]) {
                    $template_type = clone $template_types[$key][0];
                } elseif ($codebase && $atomic_type->defining_class) {
                    foreach ($template_types as $replacement_key => $template_type_map) {
                        if (!$template_type_map[1]) {
                            continue;
                        }

                        try {
                            $classlike_storage =
                                $codebase->classlike_storage_provider->get($template_type_map[1]);

                            if ($classlike_storage->template_type_extends) {
                                foreach ($classlike_storage->template_type_extends as $fq_class_name_lc => $param_map) {
                                    if (strtolower($atomic_type->defining_class) === $fq_class_name_lc
                                        && isset($param_map[$key])
                                        && isset($template_types[(string) $param_map[$key]])
                                    ) {
                                        $template_type = clone $template_types[(string) $param_map[$key]][0];
                                    }
                                }
                            }
                        } catch (\InvalidArgumentException $e) {
                        }
                    }
                }

                if (!$template_type) {
                    $template_type = Type::getMixed();
                }

                foreach ($template_type->types as $template_type_part) {
                    if ($template_type_part instanceof Type\Atomic\TMixed) {
                        $is_mixed = true;
                    }

                    $new_types[$template_type_part->getKey()] = $template_type_part;
                }
            } elseif ($atomic_type instanceof Type\Atomic\TGenericParamClass) {
                $keys_to_unset[] = $key;
                $template_type = isset($template_types[$atomic_type->param_name])
                    ? clone $template_types[$atomic_type->param_name][0]
                    : Type::getMixed();

                foreach ($template_type->types as $template_type_part) {
                    if ($template_type_part instanceof Type\Atomic\TMixed) {
                        $unknown_class_string = new Type\Atomic\TClassString();

                        $new_types[$unknown_class_string->getKey()] = $unknown_class_string;
                    } elseif ($template_type_part instanceof Type\Atomic\TNamedObject) {
                        $literal_class_string = new Type\Atomic\TClassString(
                            $template_type_part->value,
                            $template_type_part
                        );

                        $new_types[$literal_class_string->getKey()] = $literal_class_string;
                    }
                }
            } else {
                $atomic_type->replaceTemplateTypesWithArgTypes($template_types);
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
     * @return TLiteralString the only string literal represented by this union type
     * @throws \InvalidArgumentException if isSingleStringLiteral is false
     */
    public function getSingleStringLiteral()
    {
        if (count($this->types) !== 1 || count($this->literal_string_types) !== 1) {
            throw new \InvalidArgumentException("Not a string literal");
        }

        return reset($this->literal_string_types);
    }

    /**
     * @return bool true if this is a int literal with only one possible value
     */
    public function isSingleIntLiteral()
    {
        return count($this->types) === 1 && count($this->literal_int_types) === 1;
    }

    /**
     * @return TLiteralInt the only int literal represented by this union type
     * @throws \InvalidArgumentException if isSingleIntLiteral is false
     */
    public function getSingleIntLiteral()
    {
        if (count($this->types) !== 1 || count($this->literal_int_types) !== 1) {
            throw new \InvalidArgumentException("Not an int literal");
        }

        return reset($this->literal_int_types);
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
        $inferred = true
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
                $inferred
            ) === false) {
                $all_good = false;
            }
        }

        if (!$all_good) {
            return false;
        } else {
            $this->checked = true;
        }
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

    /**
     * @return bool
     */
    public function equals(Union $other_type)
    {
        if ($other_type->id && $this->id && $other_type->id !== $this->id) {
            return false;
        }

        if ($this->possibly_undefined !== $other_type->possibly_undefined) {
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
