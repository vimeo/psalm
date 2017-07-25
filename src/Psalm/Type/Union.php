<?php
namespace Psalm\Type;

use Psalm\Checker\ProjectChecker;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Type;

class Union
{
    /**
     * @var array<string, Atomic>
     */
    public $types = [];

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

    public function __clone()
    {
        foreach ($this->types as &$type) {
            $type = clone $type;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $s = '';
        foreach ($this->types as $type) {
            $s .= $type . '|';
        }

        return substr($s, 0, -1);
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        return implode(
            '|',
            array_map(
                /**
                 * @return string
                 */
                function (Atomic $type) use ($aliased_classes, $this_class, $use_phpdoc_format) {
                    return $type->toNamespacedString($aliased_classes, $this_class, $use_phpdoc_format);
                },
                $this->types
            )
        );
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
     * @return void
     */
    public function removeType($type_string)
    {
        unset($this->types[$type_string]);
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
            if ($type instanceof Atomic\Generic) {
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
    public function hasObjectLike()
    {
        return isset($this->types['array']) && $this->types['array'] instanceof Atomic\ObjectLike;
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
    public function hasString()
    {
        return isset($this->types['string']);
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
        return isset($this->types['int']) ||
            isset($this->types['float']) ||
            isset($this->types['string']);
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
    public function isVoid()
    {
        return isset($this->types['void']);
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
    public function removeObjects()
    {
        foreach ($this->types as $key => $type) {
            if ($type instanceof Atomic\TNamedObject) {
                unset($this->types[$key]);
            }
        }
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

        foreach ($old_type->types as $old_type_part) {
            $this->removeType($old_type_part->getKey());
        }

        if ($new_type) {
            foreach ($new_type->types as $key => $new_type_part) {
                $this->types[$key] = $new_type_part;
            }
        } elseif (count($this->types) === 0) {
            $this->types['mixed'] = new Atomic\TMixed();
        }
    }

    /**
     * @param  array<string, string>     $template_types
     * @param  array<string, Type\Union> $generic_params
     * @param  Type\Union|null           $input_type
     *
     * @return void
     */
    public function replaceTemplateTypes(array $template_types, array &$generic_params, Type\Union $input_type = null)
    {
        $keys_to_unset = [];

        foreach ($this->types as $key => $atomic_type) {
            if (isset($template_types[$key])) {
                $keys_to_unset[] = $key;
                $this->types[$template_types[$key]] = Atomic::create($template_types[$key]);

                if ($input_type) {
                    $generic_params[$key] = $input_type;
                }
            } else {
                $atomic_type->replaceTemplateTypes(
                    $template_types,
                    $generic_params,
                    isset($input_type->types[$key]) ? $input_type->types[$key] : null
                );
            }
        }

        foreach ($keys_to_unset as $key) {
            unset($this->types[$key]);
        }
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
     * @param  ProjectChecker $project_checker
     * @param  array<string, mixed> $phantom_classes
     *
     * @return void
     */
    public function queueClassLikesForScanning(ProjectChecker $project_checker, array $phantom_classes = [])
    {
        foreach ($this->types as $atomic_type) {
            $atomic_type->queueClassLikesForScanning($project_checker, $phantom_classes);
        }
    }
}
