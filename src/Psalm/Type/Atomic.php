<?php
namespace Psalm\Type;

use function array_filter;
use function array_keys;
use function array_search;
use function array_values;
use function count;
use function get_class;
use function is_numeric;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableObjectLikeArray;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
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
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Atomic\TVoid;
use function reset;
use function strpos;
use function strtolower;
use function substr;

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
     * @var ?int
     */
    public $offset_start;

    /**
     * @var ?int
     */
    public $offset_end;

    /**
     * @param  string $value
     * @param  array{int,int}|null   $php_version
     * @param  array<string, array<string, array{Union}>> $template_type_map
     *
     * @return Atomic
     */
    public static function create(
        $value,
        array $php_version = null,
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
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 1)
                ) {
                    return new TVoid();
                }

                break;

            case 'array-key':
                return new TArrayKey();

            case 'iterable':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 1)
                ) {
                    return new TIterable();
                }

                break;

            case 'never-return':
            case 'never-returns':
            case 'no-return':
                return new TNever();

            case 'object':
                if ($php_version === null
                    || ($php_version[0] > 7)
                    || ($php_version[0] === 7 && $php_version[1] >= 2)
                ) {
                    return new TObject();
                }

                break;

            case 'callable':
                return new TCallable();

            case 'array':
                return new TArray([new Union([new TArrayKey]), new Union([new TMixed])]);

            case 'non-empty-array':
                return new TNonEmptyArray([new Union([new TMixed]), new Union([new TMixed])]);

            case 'resource':
                return $php_version !== null ? new TNamedObject($value) : new TResource();

            case 'numeric':
                return $php_version !== null ? new TNamedObject($value) : new TNumeric();

            case 'true':
                return $php_version !== null ? new TNamedObject($value) : new TTrue();

            case 'false':
                return $php_version !== null ? new TNamedObject($value) : new TFalse();

            case 'empty':
                return $php_version !== null ? new TNamedObject($value) : new TEmpty();

            case 'scalar':
                return $php_version !== null ? new TNamedObject($value) : new TScalar();

            case 'null':
                return $php_version !== null ? new TNamedObject($value) : new TNull();

            case 'mixed':
                return $php_version !== null ? new TNamedObject($value) : new TMixed();

            case 'class-string':
            case 'interface-string':
                return new TClassString();

            case 'trait-string':
                return new TTraitString();

            case 'callable-string':
                return new TCallableString();

            case 'numeric-string':
                return new TNumericString();

            case 'html-escaped-string':
                return new THtmlEscapedString();

            case '$this':
                return new TNamedObject('static');
        }

        if (strpos($value, '-') && substr($value, 0, 4) !== 'OCI-') {
            throw new \Psalm\Exception\TypeParseTreeException('Unrecognized type ' . $value);
        }

        if (is_numeric($value[0])) {
            throw new \Psalm\Exception\TypeParseTreeException('First character of type cannot be numeric');
        }

        if (isset($template_type_map[$value])) {
            $first_class = array_keys($template_type_map[$value])[0];

            return new TTemplateParam(
                $value,
                $template_type_map[$value][$first_class][0],
                $first_class
            );
        }

        return new TNamedObject($value);
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
        return $this instanceof TObject
            || $this instanceof TNamedObject
            || ($this instanceof TTemplateParam
                && $this->as->hasObjectType());
    }

    /**
     * @return bool
     */
    public function isCallableType()
    {
        return $this instanceof TCallable
            || $this instanceof TCallableObject
            || $this instanceof TCallableString
            || $this instanceof TCallableArray
            || $this instanceof TCallableObjectLikeArray;
    }

    /**
     * @return bool
     */
    public function isIterable(Codebase $codebase)
    {
        return $this instanceof TIterable
            || $this->hasTraversableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof ObjectLike;
    }

    /**
     * @return bool
     */
    public function isCountable(Codebase $codebase)
    {
        return $this->hasCountableInterface($codebase)
            || $this instanceof TArray
            || $this instanceof ObjectLike;
    }

    /**
     * @return bool
     */
    public function hasTraversableInterface(Codebase $codebase)
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'traversable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Traversable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Traversable'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        function (Atomic $a) use ($codebase) : bool {
                            return $a->hasTraversableInterface($codebase);
                        }
                    )
                )
            );
    }

    /**
     * @return bool
     */
    public function hasCountableInterface(Codebase $codebase)
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'countable'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'Countable'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'Countable'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        function (Atomic $a) use ($codebase) : bool {
                            return $a->hasCountableInterface($codebase);
                        }
                    )
                )
            );
    }

    /**
     * @return bool
     */
    public function isArrayAccessibleWithStringKey(Codebase $codebase)
    {
        return $this instanceof TArray
            || $this instanceof ObjectLike
            || $this->hasArrayAccessInterface($codebase)
            || ($this instanceof TNamedObject && $this->value === 'SimpleXMLElement');
    }

    /**
     * @return bool
     */
    public function isArrayAccessibleWithIntOrStringKey(Codebase $codebase)
    {
        return $this instanceof TString
            || $this->isArrayAccessibleWithStringKey($codebase);
    }

    /**
     * @return bool
     */
    private function hasArrayAccessInterface(Codebase $codebase)
    {
        return $this instanceof TNamedObject
            && (
                strtolower($this->value) === 'arrayaccess'
                || ($codebase->classOrInterfaceExists($this->value)
                    && ($codebase->classExtendsOrImplements(
                        $this->value,
                        'ArrayAccess'
                    ) || $codebase->interfaceExtends(
                        $this->value,
                        'ArrayAccess'
                    )))
                || (
                    $this->extra_types
                    && array_filter(
                        $this->extra_types,
                        function (Atomic $a) use ($codebase) : bool {
                            return $a->hasArrayAccessInterface($codebase);
                        }
                    )
                )
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
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        if ($this->checked) {
            return;
        }

        if ($this instanceof TNamedObject) {
            $codebase = $source->getCodebase();

            if ($code_location instanceof CodeLocation\DocblockTypeLocation
                && $codebase->store_node_types
                && $this->offset_start !== null
                && $this->offset_end !== null
            ) {
                $codebase->analyzer->addOffsetReference(
                    $source->getFilePath(),
                    $code_location->raw_file_start + $this->offset_start,
                    $code_location->raw_file_start + $this->offset_end,
                    $this->value
                );
            }

            if (!isset($phantom_classes[strtolower($this->value)]) &&
                ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $source,
                    $this->value,
                    $code_location,
                    $suppressed_issues,
                    $inferred,
                    false,
                    true,
                    $this->from_docblock
                ) === false
            ) {
                return false;
            }

            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    if ($extra_type instanceof TTemplateParam
                        || $extra_type instanceof Type\Atomic\TObjectWithProperties
                    ) {
                        continue;
                    }

                    if ($code_location instanceof CodeLocation\DocblockTypeLocation
                        && $codebase->store_node_types
                        && $extra_type->offset_start !== null
                        && $extra_type->offset_end !== null
                    ) {
                        $codebase->analyzer->addOffsetReference(
                            $source->getFilePath(),
                            $code_location->raw_file_start + $extra_type->offset_start,
                            $code_location->raw_file_start + $extra_type->offset_end,
                            $extra_type->value
                        );
                    }

                    if (!isset($phantom_classes[strtolower($extra_type->value)]) &&
                        ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                            $source,
                            $extra_type->value,
                            $code_location,
                            $suppressed_issues,
                            $inferred,
                            false,
                            true,
                            $this->from_docblock
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
                $inferred,
                false,
                true,
                $this->from_docblock
            ) === false
            ) {
                return false;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object' && $this->as !== 'mixed') {
            if ($this->as_type) {
                if ($this->as_type->check(
                    $source,
                    $code_location,
                    $suppressed_issues,
                    $phantom_classes,
                    $inferred
                ) === false) {
                    return false;
                }
            } else {
                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                    $source,
                    $this->as,
                    $code_location,
                    $suppressed_issues,
                    $inferred,
                    false,
                    true,
                    $this->from_docblock
                ) === false
                ) {
                    return false;
                }
            }
        }

        if ($this instanceof TTemplateParam) {
            if ($prevent_template_covariance && $this->defining_class) {
                $codebase = $source->getCodebase();

                $class_storage = $codebase->classlike_storage_provider->get($this->defining_class);

                $template_offset = $class_storage->template_types
                    ? array_search($this->param_name, array_keys($class_storage->template_types), true)
                    : false;

                if ($template_offset !== false
                    && isset($class_storage->template_covariants[$template_offset])
                    && $class_storage->template_covariants[$template_offset]
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidTemplateParam(
                            'Template param ' . $this->defining_class . ' is marked covariant and cannot be used'
                                . ' as input to a function',
                            $code_location
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            $this->as->check($source, $code_location, $suppressed_issues, $phantom_classes, $inferred);
        }

        if ($this instanceof TScalarClassConstant) {
            $fq_classlike_name = $this->fq_classlike_name === 'self'
                ? $source->getClassName()
                : $this->fq_classlike_name;

            if (!$fq_classlike_name) {
                return;
            }

            if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $fq_classlike_name,
                $code_location,
                $suppressed_issues,
                $inferred,
                false,
                true,
                $this->from_docblock
            ) === false
            ) {
                return false;
            }

            $class_constants = $source->getCodebase()->classlikes->getConstantsForClass(
                $fq_classlike_name,
                \ReflectionProperty::IS_PRIVATE
            );

            if (!isset($class_constants[$this->const_name])) {
                if (IssueBuffer::accepts(
                    new UndefinedConstant(
                        'Constant ' . $fq_classlike_name . '::' . $this->const_name . ' is not defined',
                        $code_location
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
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

        if ($this instanceof Type\Atomic\TFn
            || $this instanceof Type\Atomic\TCallable
        ) {
            if ($this->params) {
                foreach ($this->params as $param) {
                    if ($param->type) {
                        $param->type->check(
                            $source,
                            $code_location,
                            $suppressed_issues,
                            $phantom_classes,
                            $inferred,
                            $prevent_template_covariance
                        );
                    }
                }
            }

            if ($this->return_type) {
                $this->return_type->check(
                    $source,
                    $code_location,
                    $suppressed_issues,
                    $phantom_classes,
                    $inferred,
                    $prevent_template_covariance
                );
            }
        }

        if ($this instanceof Type\Atomic\TArray
            || $this instanceof Type\Atomic\TGenericObject
            || $this instanceof Type\Atomic\TIterable
        ) {
            $codebase = $source->getCodebase();

            if ($this instanceof Type\Atomic\TGenericObject) {
                try {
                    $class_storage = $codebase->classlike_storage_provider->get($this->value);
                } catch (\InvalidArgumentException $e) {
                    return;
                }

                $expected_type_params = $class_storage->template_types ?: [];
            } else {
                $expected_type_params = [
                    'TKey' => [
                        '' => [Type::getMixed(), null],
                    ],
                    'TValue' => [
                        '' => [Type::getMixed(), null],
                    ],
                ];
            }

            $template_type_count = count($expected_type_params);
            $template_param_count = count($this->type_params);

            if ($template_type_count > $template_param_count) {
                if (IssueBuffer::accepts(
                    new MissingTemplateParam(
                        $this->value . ' has missing template params, expecting '
                            . $template_type_count,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            } elseif ($template_type_count < $template_param_count) {
                if (IssueBuffer::accepts(
                    new TooManyTemplateParams(
                        $this->value . ' has too many template params, expecting '
                            . $template_type_count,
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }

            foreach ($this->type_params as $i => $type_param) {
                if ($type_param->check(
                    $source,
                    $code_location,
                    $suppressed_issues,
                    $phantom_classes,
                    $inferred,
                    $prevent_template_covariance
                ) === false) {
                    return false;
                }

                if (isset(array_values($expected_type_params)[$i])) {
                    $expected_type_param = reset(array_values($expected_type_params)[$i])[0];
                    $template_name = array_keys($expected_type_params)[$i];

                    $type_param = ExpressionAnalyzer::fleshOutType(
                        $codebase,
                        $type_param,
                        $source->getFQCLN(),
                        $source->getFQCLN(),
                        $source->getParentFQCLN()
                    );

                    if (!TypeAnalyzer::isContainedBy($codebase, $type_param, $expected_type_param)) {
                        if (IssueBuffer::accepts(
                            new InvalidTemplateParam(
                                'Extended template param ' . $template_name . ' expects type '
                                    . $expected_type_param->getId()
                                    . ', type ' . $type_param->getId() . ' given',
                                $code_location
                            ),
                            $suppressed_issues
                        )) {
                            // fall through
                        }
                    }
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
                    $file_storage->referenced_classlikes[strtolower($this->value)] = $this->value;
                }
            }
        }

        if ($this instanceof TNamedObject
            || $this instanceof TIterable
            || $this instanceof TTemplateParam
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    $extra_type->queueClassLikesForScanning(
                        $codebase,
                        $file_storage,
                        $phantom_classes
                    );
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
                $file_storage->referenced_classlikes[strtolower($this->fq_classlike_name)] = $this->fq_classlike_name;
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
                $file_storage->referenced_classlikes[strtolower($this->as)] = $this->as;
            }
        }

        if ($this instanceof TTemplateParam) {
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
                $file_storage->referenced_classlikes[strtolower($this->value)] = $this->value;
            }
        }

        if ($this instanceof Type\Atomic\TArray
            || $this instanceof Type\Atomic\TGenericObject
            || $this instanceof Type\Atomic\TIterable
        ) {
            foreach ($this->type_params as $type_param) {
                $type_param->queueClassLikesForScanning(
                    $codebase,
                    $file_storage,
                    $phantom_classes
                );
            }
        }

        if ($this instanceof Type\Atomic\TFn
            || $this instanceof Type\Atomic\TCallable
        ) {
            if ($this->params) {
                foreach ($this->params as $param) {
                    if ($param->type) {
                        $param->type->queueClassLikesForScanning(
                            $codebase,
                            $file_storage,
                            $phantom_classes
                        );
                    }
                }
            }

            if ($this->return_type) {
                $this->return_type->queueClassLikesForScanning(
                    $codebase,
                    $file_storage,
                    $phantom_classes
                );
            }
        }
    }

    public function containsClassLike(string $fq_classlike_name) : bool
    {
        if ($this instanceof TNamedObject) {
            if (strtolower($this->value) === $fq_classlike_name) {
                return true;
            }
        }

        if ($this instanceof TNamedObject
            || $this instanceof TIterable
            || $this instanceof TTemplateParam
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    if ($extra_type->containsClassLike($fq_classlike_name)) {
                        return true;
                    }
                }
            }
        }

        if ($this instanceof TScalarClassConstant) {
            if (strtolower($this->fq_classlike_name) === $fq_classlike_name) {
                return true;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object') {
            if (strtolower($this->as) === $fq_classlike_name) {
                return true;
            }
        }

        if ($this instanceof TTemplateParam) {
            if ($this->as->containsClassLike($fq_classlike_name)) {
                return true;
            }
        }

        if ($this instanceof TLiteralClassString) {
            if (strtolower($this->value) === $fq_classlike_name) {
                return true;
            }
        }

        if ($this instanceof Type\Atomic\TArray
            || $this instanceof Type\Atomic\TGenericObject
            || $this instanceof Type\Atomic\TIterable
        ) {
            foreach ($this->type_params as $type_param) {
                if ($type_param->containsClassLike($fq_classlike_name)) {
                    return true;
                }
            }
        }

        if ($this instanceof Type\Atomic\ObjectLike) {
            foreach ($this->properties as $property_type) {
                if ($property_type->containsClassLike($fq_classlike_name)) {
                    return true;
                }
            }
        }

        if ($this instanceof Type\Atomic\TFn
            || $this instanceof Type\Atomic\TCallable
        ) {
            if ($this->params) {
                foreach ($this->params as $param) {
                    if ($param->type && $param->type->containsClassLike($fq_classlike_name)) {
                        return true;
                    }
                }
            }

            if ($this->return_type && $this->return_type->containsClassLike($fq_classlike_name)) {
                return true;
            }
        }

        return false;
    }

    public function replaceClassLike(string $old, string $new) : void
    {
        if ($this instanceof TNamedObject) {
            if (strtolower($this->value) === $old) {
                $this->value = $new;
            }
        }

        if ($this instanceof TNamedObject
            || $this instanceof TIterable
            || $this instanceof TTemplateParam
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as $extra_type) {
                    $extra_type->replaceClassLike($old, $new);
                }
            }
        }

        if ($this instanceof TScalarClassConstant) {
            if (strtolower($this->fq_classlike_name) === $old) {
                $this->fq_classlike_name = $new;
            }
        }

        if ($this instanceof TClassString && $this->as !== 'object') {
            if (strtolower($this->as) === $old) {
                $this->as = $new;
            }
        }

        if ($this instanceof TTemplateParam) {
            $this->as->replaceClassLike($old, $new);
        }

        if ($this instanceof TLiteralClassString) {
            if (strtolower($this->value) === $old) {
                $this->value = $new;
            }
        }

        if ($this instanceof Type\Atomic\TArray
            || $this instanceof Type\Atomic\TGenericObject
            || $this instanceof Type\Atomic\TIterable
        ) {
            foreach ($this->type_params as $type_param) {
                $type_param->replaceClassLike($old, $new);
            }
        }

        if ($this instanceof Type\Atomic\ObjectLike) {
            foreach ($this->properties as $property_type) {
                $property_type->replaceClassLike($old, $new);
            }
        }

        if ($this instanceof Type\Atomic\TFn
            || $this instanceof Type\Atomic\TCallable
        ) {
            if ($this->params) {
                foreach ($this->params as $param) {
                    if ($param->type) {
                        $param->type->replaceClassLike($old, $new);
                    }
                }
            }

            if ($this->return_type) {
                $this->return_type->replaceClassLike($old, $new);
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
        return $this->getKey() === $other->getKey()
            && !($other instanceof ObjectLike && $this instanceof ObjectLike);
    }

    public function __toString()
    {
        return '';
    }

    public function __clone()
    {
        if ($this instanceof TNamedObject
            || $this instanceof TTemplateParam
            || $this instanceof TIterable
            || $this instanceof Type\Atomic\TObjectWithProperties
        ) {
            if ($this->extra_types) {
                foreach ($this->extra_types as &$type) {
                    $type = clone $type;
                }
            }
        }
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
        return $this->getKey();
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
     * @param  array<string, array<string, array{Type\Union}>> $template_types
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $generic_params
     * @param  Type\Atomic|null          $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array &$template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Type\Atomic $input_type = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) {
        // do nothing
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase)
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
