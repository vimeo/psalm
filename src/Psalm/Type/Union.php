<?php
namespace Psalm\Type;

use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;

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
        foreach ($types as $type) {
            $this->types[$type->getKey()] = $type;
        }
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
        $this->id = null;
    }

    public function __clone()
    {
        foreach ($this->types as &$type) {
            $type = clone $type;
        }
    }

    public function __toString()
    {
        if (empty($this->types)) {
            return '';
        }
        $s = '';
        foreach ($this->types as $type) {
            $s .= $type . '|';
        }

        return substr($s, 0, -1);
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
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        return implode(
            '|',
            array_map(
                /**
                 * @return string
                 */
                function (Atomic $type) use ($namespace, $aliased_classes, $this_class, $use_phpdoc_format) {
                    return $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format);
                },
                $this->types
            )
        );
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

        if (count($this->types) > 2
            || (
                count($this->types) === 2
                && (!isset($this->types['null'])
                    || $php_major_version < 7
                    || $php_minor_version < 1)
            )
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
        if (count($this->types) > 2
            || (
                count($this->types) === 2
                && !isset($this->types['null'])
            )
        ) {
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
            $this->id = null;

            return true;
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
    public function hasGeneric()
    {
        foreach ($this->types as $type) {
            if ($type instanceof Atomic\TGenericObject || $type instanceof Atomic\TArray) {
                return true;
            }
        }

        return false;
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
    public function hasObject()
    {
        foreach ($this->types as $type) {
            if ($type instanceof Type\Atomic\TObject) {
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
        return isset($this->types['string']) || isset($this->types['class-string']);
    }

    /**
     * @return bool
     */
    public function hasInt()
    {
        return isset($this->types['int']);
    }

    /**
     * @return bool
     */
    public function hasFloat()
    {
        return isset($this->types['float']);
    }

    /**
     * @return bool
     */
    public function hasNumericType()
    {
        return isset($this->types['int'])
            || isset($this->types['float'])
            || isset($this->types['string'])
            || isset($this->types['numeric-string']);
    }

    /**
     * @return bool
     */
    public function hasScalarType()
    {
        return isset($this->types['int']) ||
            isset($this->types['float']) ||
            isset($this->types['string']) ||
            isset($this->types['bool']) ||
            isset($this->types['false']) ||
            isset($this->types['true']) ||
            isset($this->types['numeric']) ||
            isset($this->types['numeric-string']);
    }

    /**
     * @return bool
     */
    public function isMixed()
    {
        return isset($this->types['mixed']);
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
        if ($this->isMixed()) {
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
                        $this->types['array'] = new Type\Atomic\TArray([Type::getMixed(), Type::getMixed()]);
                    }

                    if ($old_type_part instanceof Type\Atomic\TArray
                        && !isset($this->types['traversable'])
                    ) {
                        $this->removeType('iterable');
                        $this->types['traversable'] = new Type\Atomic\TNamedObject('Traversable');
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
                    $combined = Type::combineTypes([$new_type_part, $this->types[$key]]);
                    $this->types[$key] = array_values($combined->types)[0];
                }
            }
        } elseif (count($this->types) === 0) {
            $this->types['mixed'] = new Atomic\TMixed();
        }

        $this->id = null;
    }

    /**
     * @param  array<string, string>     $template_types
     * @param  array<string, Type\Union> $generic_params
     * @param  Type\Union|null           $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array $template_types,
        array &$generic_params,
        Type\Union $input_type = null
    ) {
        $keys_to_unset = [];

        foreach ($this->types as $key => $atomic_type) {
            if (isset($template_types[$key])) {
                $keys_to_unset[] = $key;
                $this->types[$template_types[$key]] = Atomic::create($template_types[$key]);

                if ($input_type) {
                    $generic_params[$key] = clone $input_type;
                    $generic_params[$key]->setFromDocblock();
                }
            } else {
                $atomic_type->replaceTemplateTypesWithStandins(
                    $template_types,
                    $generic_params,
                    isset($input_type->types[$key]) ? $input_type->types[$key] : null
                );
            }
        }

        foreach ($keys_to_unset as $key) {
            unset($this->types[$key]);
        }

        $this->id = null;
    }

    /**
     * @param  array<string, string|Type\Union>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types)
    {
        $keys_to_unset = [];

        $new_types = [];

        $is_mixed = false;

        foreach ($this->types as $key => $atomic_type) {
            if (isset($template_types[$key])) {
                $keys_to_unset[] = $key;
                $template_type = $template_types[$key];

                if (is_string($template_type)) {
                    $new_types[$template_type] = Atomic::create($template_type);
                } else {
                    foreach ($template_type->types as $template_type_part) {
                        if ($template_type_part instanceof Type\Atomic\TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[$template_type_part->getKey()] = $template_type_part;
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
        if (count($this->types) > 1) {
            return false;
        }

        $type = array_values($this->types)[0];

        if (!$type instanceof Atomic\TArray && !$type instanceof Atomic\TGenericObject) {
            return true;
        }

        return $type->type_params[count($type->type_params) - 1]->isSingle();
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return void
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

        foreach ($this->types as $atomic_type) {
            $atomic_type->check($source, $code_location, $suppressed_issues, $phantom_classes, $inferred);
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
}
