<?php
namespace Psalm\Type;

use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Issue\ReservedWord;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericParam;
use Psalm\Type\Atomic\TGenericParamClass;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TScalarClassConstant;
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
     * @param  array<string, Union> $template_type_map
     *
     * @return Atomic
     */
    public static function create(
        $value,
        $php_compatible = false,
        array $template_type_map = []
    ) {
        switch ($value) {
            case 'int':
                return new TInt();

            case 'float':
                return new TFloat();

            case 'string':
                return new TString();

            case 'bool':
                return new TBool();

            case 'void':
                return new TVoid();

            case 'array-key':
                return new TArrayKey();

            case 'iterable':
                return new TIterable();

            case 'never-return':
            case 'never-returns':
            case 'no-return':
                return new TNever();

            case 'object':
                return new TObject();

            case 'callable':
                return new TCallable();

            case 'array':
                return new TArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'non-empty-array':
                return new TNonEmptyArray([new Union([new TMixed]), new Union([new TMixed])]);

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

            case 'class-string':
                return new TClassString();

            case 'numeric-string':
                return new TNumericString();

            case 'html-escaped-string':
                return new THtmlEscapedString();

            case '$this':
                return new TNamedObject('static');

            default:
                if (strpos($value, '-')) {
                    throw new \Psalm\Exception\TypeParseTreeException('no hyphens allowed');
                }

                if (is_numeric($value[0])) {
                    throw new \Psalm\Exception\TypeParseTreeException('First character of type cannot be numeric');
                }

                if (isset($template_type_map[$value])) {
                    return new TGenericParam($value, $template_type_map[$value]);
                }

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
     * @return bool
     */
    public function isCallableType()
    {
        return $this instanceof TCallable
            || $this instanceof TCallableObject
            || $this instanceof TCallableString
            || (($this instanceof TArray || $this instanceof ObjectLike) && $this->callable);
    }

    /**
     * @return bool
     */
    public function isIterable(Codebase $codebase)
    {
        return $this instanceof TIterable
            || $this->isTraversable($codebase)
            || $this instanceof TArray
            || $this instanceof ObjectLike;
    }

    /**
     * @return bool
     */
    public function isTraversable(Codebase $codebase)
    {
        return $this instanceof TNamedObject
            && (strtolower($this->value) === 'traversable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Traversable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Traversable'
                    )))
            );
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

        if ($this instanceof TNamedObject) {
            if (!isset($phantom_classes[strtolower($this->value)]) &&
                ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $source,
                    $this->value,
                    $code_location,
                    $suppressed_issues,
                    $inferred
                ) === false
            ) {
                return false;
            }

            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    if ($extra_type instanceof TGenericParam) {
                        continue;
                    }

                    if (!isset($phantom_classes[strtolower($extra_type->value)]) &&
                        ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                            $source,
                            $extra_type->value,
                            $code_location,
                            $suppressed_issues,
                            $inferred
                        ) === false
                    ) {
                        return false;
                    }
                }
            }
        }

        if ($this instanceof TLiteralClassString) {
            if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $this->value,
                $code_location,
                $suppressed_issues,
                $inferred
            ) === false
            ) {
                return false;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object' && $this->as !== 'mixed') {
            if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $this->as,
                $code_location,
                $suppressed_issues,
                $inferred
            ) === false
            ) {
                return false;
            }
        }

        if ($this instanceof TGenericParam) {
            $this->as->check($source, $code_location, $suppressed_issues, $phantom_classes, $inferred);
        }

        if ($this instanceof TScalarClassConstant) {
            if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $this->fq_classlike_name,
                $code_location,
                $suppressed_issues,
                $inferred
            ) === false
            ) {
                return false;
            }
        }

        if ($this instanceof TResource && !$this->from_docblock) {
            if (IssueBuffer::accepts(
                new ReservedWord(
                    '\'resource\' is a reserved word',
                    $code_location,
                    'resource'
                ),
                $source->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($this instanceof Type\Atomic\TArray || $this instanceof Type\Atomic\TGenericObject) {
            foreach ($this->type_params as $type_param) {
                if ($type_param->check(
                    $source,
                    $code_location,
                    $suppressed_issues,
                    $phantom_classes,
                    $inferred
                ) === false) {
                    return false;
                }
            }
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
        if ($this instanceof TNamedObject) {
            if (!isset($phantom_classes[strtolower($this->value)])) {
                $codebase->scanner->queueClassLikeForScanning(
                    $this->value,
                    $file_storage ? $file_storage->file_path : null,
                    false,
                    !$this->from_docblock
                );

                if ($file_storage) {
                    $file_storage->referenced_classlikes[] = $this->value;
                }
            }
        }

        if ($this instanceof TScalarClassConstant) {
            $codebase->scanner->queueClassLikeForScanning(
                $this->fq_classlike_name,
                $file_storage ? $file_storage->file_path : null,
                false,
                !$this->from_docblock
            );
            if ($file_storage) {
                $file_storage->referenced_classlikes[] = $this->fq_classlike_name;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object') {
            $codebase->scanner->queueClassLikeForScanning(
                $this->as,
                $file_storage ? $file_storage->file_path : null,
                false,
                !$this->from_docblock
            );
            if ($file_storage) {
                $file_storage->referenced_classlikes[] = $this->as;
            }
        }

        if ($this instanceof TGenericParam) {
            $this->as->queueClassLikesForScanning(
                $codebase,
                $file_storage,
                $phantom_classes
            );
        }

        if ($this instanceof TLiteralClassString) {
            $codebase->scanner->queueClassLikeForScanning(
                $this->value,
                $file_storage ? $file_storage->file_path : null,
                false,
                !$this->from_docblock
            );
            if ($file_storage) {
                $file_storage->referenced_classlikes[] = $this->value;
            }
        }

        if ($this instanceof Type\Atomic\TArray || $this instanceof Type\Atomic\TGenericObject) {
            foreach ($this->type_params as $type_param) {
                $type_param->queueClassLikesForScanning(
                    $codebase,
                    $file_storage,
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
     * @return string
     */
    public function getAssertionString()
    {
        return $this->getId();
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
     * @param  array<string, Type\Union> $template_types
     * @param  array<string, Type\Union> $generic_params
     * @param  Type\Atomic|null          $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array $template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Type\Atomic $input_type = null
    ) {
        // do nothing
    }

    /**
     * @param  array<string, Type\Union>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types)
    {
        // do nothing
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (get_class($other_type) !== get_class($this)) {
            return false;
        }

        return true;
    }
}
