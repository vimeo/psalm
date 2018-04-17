<?php
namespace Psalm\Checker;

use PhpParser;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Checker\FunctionLike\ReturnTypeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Codebase\CallMap;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\EffectsAnalyser;
use Psalm\FileManipulation\FunctionDocblockManipulator;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\InvalidParamDefault;
use Psalm\Issue\MismatchingDocblockParamType;
use Psalm\Issue\MissingClosureParamType;
use Psalm\Issue\MissingParamType;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UnusedParam;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

abstract class FunctionLikeChecker extends SourceChecker implements StatementsSource
{
    /**
     * @var Closure|Function_|ClassMethod
     */
    protected $function;

    /**
     * @var array<string>
     */
    protected $suppressed_issues;

    /**
     * @var bool
     */
    protected $is_static = false;

    /**
     * @var StatementsSource
     */
    protected $source;

    /**
     * @var FileChecker
     */
    public $file_checker;

    /**
     * @var array<string, array<string, Type\Union>>
     */
    protected $return_vars_in_scope = [];

    /**
     * @var array<string, array<string, bool>>
     */
    protected $return_vars_possibly_in_scope = [];

    /**
     * @var Type\Union|null
     */
    private $local_return_type;

    /**
     * @var array<string, array>
     */
    protected static $no_effects_hashes = [];

    /**
     * @param Closure|Function_|ClassMethod $function
     * @param StatementsSource $source
     */
    public function __construct($function, StatementsSource $source)
    {
        $this->function = $function;
        $this->source = $source;
        $this->file_checker = $source->getFileChecker();
        $this->suppressed_issues = $source->getSuppressedIssues();
    }

    /**
     * @param Context       $context
     * @param Context|null  $global_context
     * @param bool          $add_mutations  whether or not to add mutations to this method
     *
     * @return false|null
     */
    public function analyze(Context $context, Context $global_context = null, $add_mutations = false)
    {
        $function_stmts = $this->function->getStmts() ?: [];

        $hash = null;

        $cased_method_id = null;

        $class_storage = null;

        if ($global_context) {
            foreach ($global_context->constants as $const_name => $var_type) {
                if (!$context->hasVariable($const_name)) {
                    $context->vars_in_scope[$const_name] = clone $var_type;
                }
            }
        }

        $project_checker = $this->file_checker->project_checker;

        $file_storage_provider = $project_checker->file_storage_provider;

        $implemented_docblock_param_types = [];

        $project_checker = $this->file_checker->project_checker;
        $codebase = $project_checker->codebase;

        $classlike_storage_provider = $project_checker->classlike_storage_provider;

        if ($this->function instanceof ClassMethod) {
            $real_method_id = (string)$this->getMethodId();

            $method_id = (string)$this->getMethodId($context->self);

            if ($add_mutations) {
                $hash = $real_method_id . json_encode([
                    $context->vars_in_scope,
                        $context->vars_possibly_in_scope,
                    ]);

                // if we know that the function has no effects on vars, we don't bother rechecking
                if (isset(self::$no_effects_hashes[$hash])) {
                    list(
                        $context->vars_in_scope,
                        $context->vars_possibly_in_scope
                    ) = self::$no_effects_hashes[$hash];

                    return null;
                }
            } elseif ($context->self) {
                $context->vars_in_scope['$this'] = new Type\Union([new TNamedObject($context->self)]);
                $context->vars_possibly_in_scope['$this'] = true;
            }

            $fq_class_name = (string)$context->self;

            $class_storage = $classlike_storage_provider->get($fq_class_name);

            try {
                $storage = $codebase->methods->getStorage($real_method_id);
            } catch (\UnexpectedValueException $e) {
                if (!$class_storage->parent_classes) {
                    throw $e;
                }

                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if (!$declaring_method_id) {
                    throw $e;
                }

                // happens for fake constructors
                $storage = $codebase->methods->getStorage($declaring_method_id);
            }

            $cased_method_id = $fq_class_name . '::' . $storage->cased_name;

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($method_id);

            if ($this->function->name->name === '__construct') {
                $context->inside_constructor = true;
            }

            if ($overridden_method_ids
                && $this->function->name->name !== '__construct'
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    list($overridden_fq_class_name) = explode('::', $overridden_method_id);

                    $parent_storage = $classlike_storage_provider->get($overridden_fq_class_name);

                    MethodChecker::compareMethods(
                        $project_checker,
                        $class_storage,
                        $parent_storage,
                        $storage,
                        $parent_method_storage,
                        new CodeLocation(
                            $this,
                            $this->function,
                            null,
                            true
                        ),
                        $storage->suppressed_issues
                    );

                    foreach ($parent_method_storage->params as $i => $guide_param) {
                        if ($guide_param->type && (!$guide_param->signature_type || !$parent_storage->user_defined)) {
                            $implemented_docblock_param_types[$i] = true;
                        }
                    }
                }
            }
        } elseif ($this->function instanceof Function_) {
            $file_storage = $file_storage_provider->get($this->source->getFilePath());

            $storage = $file_storage->functions[(string)$this->getMethodId()];

            $cased_method_id = $this->function->name;
        } else { // Closure
            $function_id = $this->getMethodId();

            $storage = $codebase->getClosureStorage($this->source->getFilePath(), $function_id);

            if ($storage->return_type) {
                $closure_return_type = ExpressionChecker::fleshOutType(
                    $project_checker,
                    $storage->return_type,
                    $context->self,
                    $context->self
                );
            } else {
                $closure_return_type = Type::getMixed();
            }

            /** @var PhpParser\Node\Expr\Closure $this->function */
            $this->function->inferredType = new Type\Union([
                new Type\Atomic\Fn(
                    'Closure',
                    $storage->params,
                    $closure_return_type
                ),
            ]);
        }

        $this->suppressed_issues = array_merge(
            $this->getSource()->getSuppressedIssues(),
            $storage->suppressed_issues
        );

        if ($storage instanceof MethodStorage && $storage->is_static) {
            $this->is_static = true;
        }

        $statements_checker = new StatementsChecker($this);

        $template_types = $storage->template_types;

        if ($class_storage && $class_storage->template_types) {
            $template_types = array_merge($template_types ?: [], $class_storage->template_types);
        }

        foreach ($storage->params as $offset => $function_param) {
            $signature_type = $function_param->signature_type;

            if ($function_param->type) {
                $param_type = clone $function_param->type;

                $param_type = ExpressionChecker::fleshOutType(
                    $project_checker,
                    $param_type,
                    $context->self,
                    $context->self
                );
            } else {
                $param_type = Type::getMixed();
            }

            $context->vars_in_scope['$' . $function_param->name] = $param_type;
            $context->vars_possibly_in_scope['$' . $function_param->name] = true;

            if ($context->collect_references && $function_param->location) {
                $context->unreferenced_vars['$' . $function_param->name] = $function_param->location;
            }

            if (!$function_param->type_location || !$function_param->location) {
                continue;
            }

            /**
             * @psalm-suppress MixedArrayAccess
             *
             * @var PhpParser\Node\Param
             */
            $parser_param = $this->function->getParams()[$offset];

            if ($signature_type) {
                if (!TypeChecker::isContainedBy(
                    $codebase,
                    $param_type,
                    $signature_type
                )
                ) {
                    if ($project_checker->alter_code
                        && isset($project_checker->getIssuesToFix()['MismatchingDocblockParamType'])
                    ) {
                        $this->addOrUpdateParamType($project_checker, $function_param->name, $signature_type, true);

                        continue;
                    }

                    if (IssueBuffer::accepts(
                        new MismatchingDocblockParamType(
                            'Parameter $' . $function_param->name . ' has wrong type \'' . $param_type .
                                '\', should be \'' . $signature_type . '\'',
                            $function_param->type_location
                        ),
                        $storage->suppressed_issues
                    )) {
                        return false;
                    }

                    $signature_type->check(
                        $this,
                        $function_param->type_location,
                        $storage->suppressed_issues,
                        [],
                        false
                    );

                    continue;
                }
            }

            if ($parser_param->default) {
                ExpressionChecker::analyze($statements_checker, $parser_param->default, $context);

                $default_type = isset($parser_param->default->inferredType)
                    ? $parser_param->default->inferredType
                    : null;

                if ($default_type
                    && !$default_type->isMixed()
                    && !TypeChecker::isContainedBy(
                        $codebase,
                        $default_type,
                        $param_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidParamDefault(
                            'Default value type ' . $default_type . ' for argument ' . ($offset + 1)
                                . ' of method ' . $cased_method_id
                                . ' does not match the given type ' . $param_type,
                            $function_param->type_location
                        )
                    )) {
                        // fall through
                    }
                }
            }

            if ($template_types) {
                $substituted_type = clone $param_type;
                $generic_types = [];
                $substituted_type->replaceTemplateTypesWithStandins($template_types, $generic_types, null);
                $substituted_type->check(
                    $this->source,
                    $function_param->type_location,
                    $this->suppressed_issues,
                    [],
                    false
                );
            } else {
                if ($param_type->isVoid()) {
                    if (IssueBuffer::accepts(
                        new ReservedWord(
                            'Parameter cannot be void',
                            $function_param->type_location,
                            'void'
                        ),
                        $this->suppressed_issues
                    )) {
                        // fall through
                    }
                }

                $param_type->check(
                    $this->source,
                    $function_param->type_location,
                    $this->suppressed_issues,
                    [],
                    false
                );
            }

            if ($codebase->collect_references) {
                if ($function_param->type_location !== $function_param->signature_type_location &&
                    $function_param->signature_type_location &&
                    $function_param->signature_type
                ) {
                    $function_param->signature_type->check(
                        $this->source,
                        $function_param->signature_type_location,
                        $this->suppressed_issues,
                        [],
                        false
                    );
                }
            }

            if ($function_param->by_ref) {
                $context->byref_constraints['$' . $function_param->name]
                    = new \Psalm\ReferenceConstraint(!$param_type->isMixed() ? $param_type : null);
            }

            if ($function_param->by_ref) {
                // register by ref params as having been used, to avoid false positives
                // @todo change the assignment analysis *just* for byref params
                // so that we don't have to do this
                $context->hasVariable('$' . $function_param->name);
            }

            $statements_checker->registerVariable(
                '$' . $function_param->name,
                $function_param->location,
                null
            );
        }

        if (ReturnTypeChecker::checkSignatureReturnType(
            $this->function,
            $project_checker,
            $this,
            $storage,
            $context
        ) === false) {
            return false;
        }

        $statements_checker->analyze($function_stmts, $context, null, $global_context, true);

        foreach ($storage->params as $offset => $function_param) {
            // only complain if there's no type defined by a parent type
            if (!$function_param->type
                && $function_param->location
                && !isset($implemented_docblock_param_types[$offset])
            ) {
                $possible_type = null;

                if (isset($context->possible_param_types[$function_param->name])) {
                    $possible_type = $context->possible_param_types[$function_param->name];
                }

                $infer_text = $project_checker->infer_types_from_usage
                    ? ', ' . ($possible_type ? 'should be ' . $possible_type : 'could not infer type')
                    : '';

                if ($this->function instanceof Closure) {
                    IssueBuffer::accepts(
                        new MissingClosureParamType(
                            'Parameter $' . $function_param->name . ' has no provided type' . $infer_text,
                            $function_param->location
                        ),
                        $storage->suppressed_issues
                    );
                } else {
                    IssueBuffer::accepts(
                        new MissingParamType(
                            'Parameter $' . $function_param->name . ' has no provided type' . $infer_text,
                            $function_param->location
                        ),
                        $storage->suppressed_issues
                    );
                }
            }
        }

        if ($this->function instanceof Closure) {
            $this->verifyReturnType(
                $storage->return_type,
                $this->source->getFQCLN(),
                $storage->return_type_location
            );

            $closure_yield_types = [];

            $closure_return_types = EffectsAnalyser::getReturnTypes(
                $this->function->stmts,
                $closure_yield_types,
                $ignore_nullable_issues,
                $ignore_falsable_issues,
                true
            );

            if ($closure_return_types) {
                $closure_return_type = new Type\Union($closure_return_types);

                if (!$storage->return_type
                    || $storage->return_type->isMixed()
                    || TypeChecker::isContainedBy(
                        $project_checker->codebase,
                        $closure_return_type,
                        $storage->return_type
                    )
                ) {
                    if ($this->function->inferredType) {
                        /** @var Type\Atomic\Fn */
                        $closure_atomic = $this->function->inferredType->getTypes()['Closure'];
                        $closure_atomic->return_type = $closure_return_type;
                    }
                }
            }
        }

        if ($context->collect_references
            && !$context->collect_initializations
            && $project_checker->codebase->find_unused_code
            && $context->check_variables
        ) {
            foreach ($statements_checker->getUnusedVarLocations() as list($var_name, $original_location)) {
                if (!array_key_exists(substr($var_name, 1), $storage->param_types)) {
                    continue;
                }

                if (strpos($var_name, '$_') === 0 || (strpos($var_name, '$unused') === 0 && $var_name !== '$unused')) {
                    continue;
                }

                $position = array_search(substr($var_name, 1), array_keys($storage->param_types), true);

                if ($position === false) {
                    throw new \UnexpectedValueException('$position should not be false here');
                }

                if ($storage->params[$position]->by_ref) {
                    continue;
                }

                if (!($storage instanceof MethodStorage)
                    || $storage->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
                ) {
                    if (IssueBuffer::accepts(
                        new UnusedParam(
                            'Param ' . $var_name . ' is never referenced in this method',
                            $original_location
                        ),
                        $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    $fq_class_name = (string)$context->self;

                    $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

                    $method_name_lc = strtolower($storage->cased_name);

                    if ($storage->abstract || !isset($class_storage->overridden_method_ids[$method_name_lc])) {
                        continue;
                    }

                    $parent_method_id = end($class_storage->overridden_method_ids[$method_name_lc]);

                    if ($parent_method_id) {
                        $parent_method_storage = $codebase->methods->getStorage($parent_method_id);

                        // if the parent method has a param at that position and isn't abstract
                        if (!$parent_method_storage->abstract
                            && isset($parent_method_storage->params[$position])
                        ) {
                            continue;
                        }
                    }

                    $storage->unused_params[$position] = $original_location;
                }
            }

            if ($storage instanceof MethodStorage && $class_storage) {
                foreach ($storage->params as $i => $_) {
                    if (!isset($storage->unused_params[$i])) {
                        $storage->used_params[$i] = true;

                        /** @var ClassMethod $this->function */
                        $method_name_lc = strtolower($storage->cased_name);

                        if (!isset($class_storage->overridden_method_ids[$method_name_lc])) {
                            continue;
                        }

                        foreach ($class_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                            $parent_method_storage = $codebase->methods->getStorage($parent_method_id);

                            $parent_method_storage->used_params[$i] = true;
                        }
                    }
                }
            }
        }

        if ($add_mutations) {
            if (isset($this->return_vars_in_scope[''])) {
                $context->vars_in_scope = TypeChecker::combineKeyedTypes(
                    $context->vars_in_scope,
                    $this->return_vars_in_scope['']
                );
            }

            if (isset($this->return_vars_possibly_in_scope[''])) {
                $context->vars_possibly_in_scope = array_merge(
                    $context->vars_possibly_in_scope,
                    $this->return_vars_possibly_in_scope['']
                );
            }

            foreach ($context->vars_in_scope as $var => $_) {
                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                    unset($context->vars_in_scope[$var]);
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                    unset($context->vars_possibly_in_scope[$var]);
                }
            }

            if ($hash && $this instanceof MethodChecker) {
                self::$no_effects_hashes[$hash] = [
                    $context->vars_in_scope,
                    $context->vars_possibly_in_scope,
                ];
            }
        }

        return null;
    }

    /**
     * @param Type\Union|null     $return_type
     * @param string              $fq_class_name
     * @param CodeLocation|null   $return_type_location
     *
     * @return  false|null
     */
    public function verifyReturnType(
        Type\Union $return_type = null,
        $fq_class_name = null,
        CodeLocation $return_type_location = null
    ) {
        ReturnTypeChecker::verifyReturnType(
            $this->function,
            $this->source,
            $this,
            $return_type,
            $fq_class_name,
            $return_type_location
        );
    }

    /**
     * @param string $param_name
     * @param bool $docblock_only
     *
     * @return void
     */
    private function addOrUpdateParamType(
        ProjectChecker $project_checker,
        $param_name,
        Type\Union $inferred_return_type,
        $docblock_only = false
    ) {
        $manipulator = FunctionDocblockManipulator::getForFunction(
            $project_checker,
            $this->source->getFilePath(),
            $this->getMethodId(),
            $this->function
        );
        $manipulator->setParamType(
            $param_name,
            !$docblock_only && $project_checker->php_major_version >= 7
                ? $inferred_return_type->toPhpString(
                    $this->source->getNamespace(),
                    $this->source->getAliasedClassesFlipped(),
                    $this->source->getFQCLN(),
                    $project_checker->php_major_version,
                    $project_checker->php_minor_version
                ) : null,
            $inferred_return_type->toNamespacedString(
                $this->source->getNamespace(),
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                false
            ),
            $inferred_return_type->toNamespacedString(
                $this->source->getNamespace(),
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                true
            ),
            $inferred_return_type->canBeFullyExpressedInPhp()
        );
    }

    /**
     * Adds return types for the given function
     *
     * @param   string  $return_type
     * @param   Context $context
     *
     * @return  void
     */
    public function addReturnTypes($return_type, Context $context)
    {
        if (isset($this->return_vars_in_scope[$return_type])) {
            $this->return_vars_in_scope[$return_type] = TypeChecker::combineKeyedTypes(
                $context->vars_in_scope,
                $this->return_vars_in_scope[$return_type]
            );
        } else {
            $this->return_vars_in_scope[$return_type] = $context->vars_in_scope;
        }

        if (isset($this->return_vars_possibly_in_scope[$return_type])) {
            $this->return_vars_possibly_in_scope[$return_type] = array_merge(
                $context->vars_possibly_in_scope,
                $this->return_vars_possibly_in_scope[$return_type]
            );
        } else {
            $this->return_vars_possibly_in_scope[$return_type] = $context->vars_possibly_in_scope;
        }
    }

    /**
     * @return null|string
     */
    public function getMethodName()
    {
        if ($this->function instanceof ClassMethod) {
            return (string)$this->function->name;
        }
    }

    /**
     * @param string|null $context_self
     *
     * @return string
     */
    public function getMethodId($context_self = null)
    {
        if ($this->function instanceof ClassMethod) {
            $function_name = (string)$this->function->name;

            return ($context_self ?: $this->source->getFQCLN()) . '::' . strtolower($function_name);
        }

        if ($this->function instanceof Function_) {
            $namespace = $this->source->getNamespace();

            return ($namespace ? strtolower($namespace) . '\\' : '') . strtolower($this->function->name->name);
        }

        return $this->getFilePath() . ':' . $this->function->getLine() . ':-:closure';
    }

    /**
     * @param string|null $context_self
     *
     * @return string
     */
    public function getCorrectlyCasedMethodId($context_self = null)
    {
        if ($this->function instanceof ClassMethod) {
            $function_name = (string)$this->function->name;

            return ($context_self ?: $this->source->getFQCLN()) . '::' . $function_name;
        }

        if ($this->function instanceof Function_) {
            $namespace = $this->source->getNamespace();

            return ($namespace ? $namespace . '\\' : '') . $this->function->name;
        }

        return $this->getFilePath() . ':' . $this->function->getLine() . ':-:closure';
    }

    /**
     * @return FunctionLikeStorage
     */
    public function getFunctionLikeStorage(StatementsChecker $statements_checker = null)
    {
        $project_checker = $this->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        if ($this->function instanceof ClassMethod) {
            $method_id = (string) $this->getMethodId();
            $codebase_methods = $codebase->methods;

            try {
                return $codebase_methods->getStorage($method_id);
            } catch (\UnexpectedValueException $e) {
                $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

                if (!$declaring_method_id) {
                    throw new \UnexpectedValueException('Cannot get storage for function that doesn‘t exist');
                }

                // happens for fake constructors
                return $codebase_methods->getStorage($declaring_method_id);
            }
        }

        return $codebase->functions->getStorage($statements_checker, (string) $this->getMethodId());
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        if ($this->source instanceof NamespaceChecker ||
            $this->source instanceof FileChecker ||
            $this->source instanceof ClassLikeChecker
        ) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @return string|null
     */
    public function getFQCLN()
    {
        return $this->source->getFQCLN();
    }

    /**
     * @return null|string
     */
    public function getClassName()
    {
        return $this->source->getClassName();
    }

    /**
     * @return string|null
     */
    public function getParentFQCLN()
    {
        return $this->source->getParentFQCLN();
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return $this->is_static;
    }

    /**
     * @return StatementsSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param  string                           $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     *
     * @return array<int, FunctionLikeParameter>
     */
    public static function getMethodParamsById(ProjectChecker $project_checker, $method_id, array $args)
    {
        $fq_class_name = strpos($method_id, '::') !== false ? explode('::', $method_id)[0] : null;

        $codebase = $project_checker->codebase;

        if ($fq_class_name) {
            $class_storage = $project_checker->codebase->classlike_storage_provider->get($fq_class_name);

            if ($class_storage->user_defined || $class_storage->stubbed) {
                $method_params = $codebase->methods->getMethodParams($method_id);

                return $method_params;
            }
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if (CallMap::inCallMap($declaring_method_id ?: $method_id)) {
            $function_param_options = CallMap::getParamsFromCallMap($declaring_method_id ?: $method_id);

            if ($function_param_options === null) {
                throw new \UnexpectedValueException(
                    'Not expecting $function_param_options to be null for ' . $method_id
                );
            }

            return self::getMatchingParamsFromCallMapOptions($project_checker, $function_param_options, $args);
        }

        return $codebase->methods->getMethodParams($method_id);
    }

    /**
     * @param  string                           $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     *
     * @return array<int, FunctionLikeParameter>
     */
    public static function getFunctionParamsFromCallMapById(ProjectChecker $project_checker, $method_id, array $args)
    {
        $function_param_options = CallMap::getParamsFromCallMap($method_id);

        if ($function_param_options === null) {
            throw new \UnexpectedValueException(
                'Not expecting $function_param_options to be null for ' . $method_id
            );
        }

        return self::getMatchingParamsFromCallMapOptions($project_checker, $function_param_options, $args);
    }

    /**
     * @param  array<int, array<int, FunctionLikeParameter>>  $function_param_options
     * @param  array<int, PhpParser\Node\Arg>                 $args
     *
     * @return array<int, FunctionLikeParameter>
     */
    protected static function getMatchingParamsFromCallMapOptions(
        ProjectChecker $project_checker,
        array $function_param_options,
        array $args
    ) {
        if (count($function_param_options) === 1) {
            return $function_param_options[0];
        }

        foreach ($function_param_options as $possible_function_params) {
            $all_args_match = true;

            $last_param = count($possible_function_params)
                ? $possible_function_params[count($possible_function_params) - 1]
                : null;

            $mandatory_param_count = count($possible_function_params);

            foreach ($possible_function_params as $i => $possible_function_param) {
                if ($possible_function_param->is_optional) {
                    $mandatory_param_count = $i;
                    break;
                }
            }

            if ($mandatory_param_count > count($args)) {
                continue;
            }

            foreach ($args as $argument_offset => $arg) {
                if ($argument_offset >= count($possible_function_params)) {
                    if (!$last_param || !$last_param->is_variadic) {
                        $all_args_match = false;
                    }

                    break;
                }

                $param_type = $possible_function_params[$argument_offset]->type;

                if (!$param_type) {
                    continue;
                }

                if (!isset($arg->value->inferredType)) {
                    continue;
                }

                if ($arg->value->inferredType->isMixed()) {
                    continue;
                }

                if (TypeChecker::isContainedBy($project_checker->codebase, $arg->value->inferredType, $param_type)) {
                    continue;
                }

                $all_args_match = false;
                break;
            }

            if ($all_args_match) {
                return $possible_function_params;
            }
        }

        // if we don't succeed in finding a match, set to the first possible and wait for issues below
        return $function_param_options[0];
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues)
    {
        $this->suppressed_issues = array_merge($new_issues, $this->suppressed_issues);
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues)
    {
        $this->suppressed_issues = array_diff($this->suppressed_issues, $new_issues);
    }

    /**
     * Adds a suppressed issue, useful when creating a method checker from scratch
     *
     * @param string $issue_name
     *
     * @return void
     */
    public function addSuppressedIssue($issue_name)
    {
        $this->suppressed_issues[] = $issue_name;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$no_effects_hashes = [];
    }

    /**
     * @return FileChecker
     */
    public function getFileChecker()
    {
        return $this->file_checker;
    }

    /**
     * @return Type\Union
     */
    public function getLocalReturnType(Type\Union $storage_return_type)
    {
        if ($this->local_return_type) {
            return $this->local_return_type;
        }

        $this->local_return_type = ExpressionChecker::fleshOutType(
            $this->file_checker->project_checker,
            $storage_return_type,
            $this->getFQCLN(),
            $this->getFQCLN()
        );

        return $this->local_return_type;
    }
}
