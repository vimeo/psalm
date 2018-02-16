<?php
namespace Psalm\Type;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Issue\ReservedWord;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;

abstract class Atomic
{
    const KEY = 'atomic';

    /**
     * Whether or not the type has been checked yet
     *
     * @var bool
     */
    protected $checked = false;

    /**
     * Whether or not the type comes from a docblock
     *
     * @var bool
     */
    public $from_docblock = false;

    /**
     * @param  string $value
     * @param  bool   $php_compatible
     *
     * @return Atomic
     */
    public static function create($value, $php_compatible = false)
    {
        switch ($value) {
            case 'int':
                return new TInt();

            case 'void':
                return new TVoid();

            case 'float':
                return new TFloat();

            case 'string':
                return new TString();

            case 'bool':
                return new TBool();

            case 'object':
                return new TObject();

            case 'callable':
                return new TCallable();

            case 'array':
                return new TArray([new Union([new TMixed]), new Union([new TMixed])]);

            case 'resource':
                return $php_compatible ? new TNamedObject($value) : new TResource();

            case 'numeric':
                return $php_compatible ? new TNamedObject($value) : new TNumeric();

            case 'true':
                return $php_compatible ? new TNamedObject($value) : new TTrue();

            case 'false':
                return $php_compatible ? new TNamedObject($value) : new TFalse();

            case 'empty':
                return $php_compatible ? new TNamedObject($value) : new TEmpty();

            case 'scalar':
                return $php_compatible ? new TNamedObject($value) : new TScalar();

            case 'null':
                return $php_compatible ? new TNamedObject($value) : new TNull();

            case 'mixed':
                return $php_compatible ? new TNamedObject($value) : new TMixed();

            case 'numeric-string':
                return new TNumericString();

            default:
                return new TNamedObject($value);
        }
    }

    /**
     * @return string
     */
    abstract public function getKey();

    /**
     * @return bool
     */
    public function isNumericType()
    {
        return $this instanceof TInt
            || $this instanceof TFloat
            || $this instanceof TNumericString
            || $this instanceof TNumeric;
    }

    /**
     * @return bool
     */
    public function isObjectType()
    {
        return $this instanceof TObject || $this instanceof TNamedObject;
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return false|null
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

        if ($this instanceof TNamedObject &&
            !isset($phantom_classes[strtolower($this->value)]) &&
            ClassLikeChecker::checkFullyQualifiedClassLikeName(
                $source,
                $this->value,
                $code_location,
                $suppressed_issues,
                $inferred
            ) === false
        ) {
            return false;
        }

        if ($this instanceof TResource && !$this->from_docblock) {
            if (IssueBuffer::accepts(
                new ReservedWord(
                    '\'resource\' is a reserved word',
                    $code_location
                ),
                $source->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($this instanceof Type\Atomic\TArray || $this instanceof Type\Atomic\TGenericObject) {
            foreach ($this->type_params as $type_param) {
                $type_param->check($source, $code_location, $suppressed_issues, $phantom_classes, $inferred);
            }
        }

        $this->checked = true;
    }

    /**
     * @param  string $referencing_file_path
     * @param  array<string, mixed> $phantom_classes
     *
     * @return void
     */
    public function queueClassLikesForScanning(
        Codebase $codebase,
        $referencing_file_path = null,
        array $phantom_classes = []
    ) {
        if ($this instanceof TNamedObject && !isset($phantom_classes[strtolower($this->value)])) {
            $codebase->scanner->queueClassLikeForScanning($this->value, $referencing_file_path);

            return;
        }

        if ($this instanceof Type\Atomic\TArray || $this instanceof Type\Atomic\TGenericObject) {
            foreach ($this->type_params as $type_param) {
                $type_param->queueClassLikesForScanning(
                    $codebase,
                    $referencing_file_path,
                    $phantom_classes
                );
            }
        }
    }

    /**
     * @param  Atomic $other
     *
     * @return bool
     */
    public function shallowEquals(Atomic $other)
    {
        return strtolower($this->getKey()) === strtolower($other->getKey())
            && !($other instanceof ObjectLike && $this instanceof ObjectLike);
    }

    public function __toString()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->__toString();
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
        return $this->getKey();
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
    abstract public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    );

    /**
     * @return bool
     */
    abstract public function canBeFullyExpressedInPhp();

    /**
     * @return void
     */
    public function setFromDocblock()
    {
        $this->from_docblock = true;
    }

    /**
     * @param  array<string, string>     $template_types
     * @param  array<string, Type\Union> $generic_params
     * @param  Type\Atomic|null          $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array $template_types,
        array &$generic_params,
        Type\Atomic $input_type = null
    ) {
        // do nothing
    }

    /**
     * @param  array<string, string|Type\Union>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types)
    {
        // do nothing
    }
}
