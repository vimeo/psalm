<?php
namespace Psalm\Checker;

use PhpParser;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Aliases;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\EffectsAnalyser;
use Psalm\FileManipulation\FunctionDocblockManipulator;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidParamDefault;
use Psalm\Issue\InvalidReturnType;
use Psalm\Issue\InvalidToString;
use Psalm\Issue\LessSpecificReturnType;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MissingClosureReturnType;
use Psalm\Issue\MissingReturnType;
use Psalm\Issue\MixedInferredReturnType;
use Psalm\Issue\MoreSpecificReturnType;
use Psalm\Issue\OverriddenMethodAccess;
use Psalm\Issue\PossiblyUnusedVariable;
use Psalm\Issue\UntypedParam;
use Psalm\Issue\UnusedVariable;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

abstract class FunctionLikeChecker extends SourceChecker implements StatementsSource
{
    const RETURN_TYPE_REGEX = '/\\:\s+(\\??[A-Za-z0-9_\\\\\[\]]+)/';
    const PARAM_TYPE_REGEX = '/^(\\??[A-Za-z0-9_\\\\\[\]]+)\s/';

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
     * @var StatementsChecker|null
     */
    protected $statements_checker;

    /**
     * @var StatementsSource
     */
    protected $source;

    /** @var FileChecker */
    public $file_checker;

    /**
     * @var array<string, array<string, Type\Union>>
     */
    protected $return_vars_in_scope = [];

    /**
     * @var array<string, array<string, bool|string>>
     */
    protected $return_vars_possibly_in_scope = [];

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
        /** @var array<PhpParser\Node\Expr|PhpParser\Node\Stmt> */
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

            $declaring_method_id = MethodChecker::getDeclaringMethodId($project_checker, $method_id);

            if (!is_string($declaring_method_id)) {
                throw new \UnexpectedValueException('The declaring method of ' . $method_id . ' should not be null');
            }

            $fq_class_name = (string)$context->self;

            $project_checker = $this->file_checker->project_checker;

            $classlike_storage_provider = $project_checker->classlike_storage_provider;

            $class_storage = $classlike_storage_provider->get($fq_class_name);

            $storage = MethodChecker::getStorage($project_checker, $declaring_method_id);

            $cased_method_id = $fq_class_name . '::' . $storage->cased_name;

            $implemented_method_ids = MethodChecker::getOverriddenMethodIds($project_checker, $method_id);

            if ($this->function->name === '__construct') {
                $context->inside_constructor = true;
            }

            $implemented_docblock_param_types = [];

            if ($implemented_method_ids) {
                $have_emitted = false;

                foreach ($implemented_method_ids as $implemented_method_id) {
                    if ($this->function->name === '__construct') {
                        continue;
                    }

                    list($implemented_fq_class_name) = explode('::', $implemented_method_id);

                    $class_storage = $classlike_storage_provider->get($implemented_fq_class_name);

                    $implemented_storage = MethodChecker::getStorage($project_checker, $implemented_method_id);

                    if ($implemented_storage->visibility < $storage->visibility) {
                        $parent_method_id = MethodChecker::getCasedMethodId(
                            $project_checker,
                            $implemented_method_id
                        );

                        if (IssueBuffer::accepts(
                            new OverriddenMethodAccess(
                                'Method ' . $cased_method_id . ' has different access level than ' . $parent_method_id,
                                new CodeLocation($this, $this->function, $context->include_location, true)
                            )
                        )) {
                            return false;
                        }

                        continue;
                    }

                    $implemented_params = $implemented_storage->params;

                    foreach ($implemented_params as $i => $implemented_param) {
                        if (!isset($storage->params[$i])) {
                            $parent_method_id = MethodChecker::getCasedMethodId(
                                $project_checker,
                                $implemented_method_id
                            );

                            if (IssueBuffer::accepts(
                                new MethodSignatureMismatch(
                                    'Method ' . $cased_method_id . ' has fewer arguments than parent method ' .
                                        $parent_method_id,
                                    new CodeLocation($this, $this->function, $context->include_location, true)
                                )
                            )) {
                                return false;
                            }

                            $have_emitted = true;
                            break 2;
                        }

                        $or_null_implemented_type = $implemented_param->signature_type
                            ? clone $implemented_param->signature_type
                            : null;

                        if ($or_null_implemented_type) {
                            $or_null_implemented_type->types['null'] = new Type\Atomic\TNull;
                        }

                        if ($class_storage->user_defined
                            && (string)$storage->params[$i]->signature_type
                                !== (string)$implemented_param->signature_type
                            && (string)$storage->params[$i]->signature_type
                                !== (string)$or_null_implemented_type
                        ) {
                            $cased_method_id = MethodChecker::getCasedMethodId(
                                $project_checker,
                                (string)$this->getMethodId()
                            );
                            $parent_method_id = MethodChecker::getCasedMethodId(
                                $project_checker,
                                $implemented_method_id
                            );

                            if (IssueBuffer::accepts(
                                new MethodSignatureMismatch(
                                    'Argument ' . ($i + 1) . ' of ' . $cased_method_id . ' has wrong type \'' .
                                        $storage->params[$i]->signature_type . '\', expecting \'' .
                                        $implemented_param->signature_type . '\' as defined by ' .
                                        $parent_method_id,
                                    $storage->params[$i]->location
                                        ?: new CodeLocation(
                                            $this,
                                            $this->function,
                                            $context->include_location,
                                            true
                                        )
                                )
                            )) {
                                return false;
                            }

                            $have_emitted = true;
                            break 2;
                        }

                        if ($implemented_param->type
                            && (!$implemented_param->signature_type || !$class_storage->user_defined)
                        ) {
                            $implemented_docblock_param_types[$i] = true;
                        }

                        if (!$class_storage->user_defined &&
                            $implemented_param->type &&
                            !$implemented_param->type->isMixed() &&
                            (string)$storage->params[$i]->type !== (string)$implemented_param->type
                        ) {
                            $cased_method_id = MethodChecker::getCasedMethodId(
                                $project_checker,
                                (string)$this->getMethodId()
                            );
                            $parent_method_id = MethodChecker::getCasedMethodId(
                                $project_checker,
                                $implemented_method_id
                            );

                            if (IssueBuffer::accepts(
                                new MethodSignatureMismatch(
                                    'Argument ' . ($i + 1) . ' of ' . $cased_method_id . ' has wrong type \'' .
                                        $storage->params[$i]->type . '\', expecting \'' .
                                        $implemented_param->type . '\' as defined by ' .
                                        $parent_method_id,
                                    $storage->params[$i]->location
                                        ?: new CodeLocation(
                                            $this,
                                            $this->function,
                                            $context->include_location,
                                            true
                                        )
                                )
                            )) {
                                return false;
                            }

                            $have_emitted = true;
                            break 2;
                        }
                    }

                    if ($storage->cased_name !== '__construct' &&
                        $storage->required_param_count > $implemented_storage->required_param_count
                    ) {
                        $parent_method_id = MethodChecker::getCasedMethodId($project_checker, $implemented_method_id);

                        if (IssueBuffer::accepts(
                            new MethodSignatureMismatch(
                                'Method ' . $cased_method_id . ' has more arguments than parent method ' .
                                    $parent_method_id,
                                new CodeLocation($this, $this->function, $context->include_location, true)
                            )
                        )) {
                            return false;
                        }

                        $have_emitted = true;
                        break;
                    }
                }
            }
        } elseif ($this->function instanceof Function_) {
            $file_storage = $file_storage_provider->get($this->source->getFilePath());

            $storage = $file_storage->functions[(string)$this->getMethodId()];

            $cased_method_id = $this->function->name;
        } else { // Closure
            $file_storage = $file_storage_provider->get($this->source->getFilePath());

            $function_id = $this->getMethodId();

            if (!isset($file_storage->functions[$function_id])) {
                throw new \UnexpectedValueException('Closure function ' . $function_id . ' should exist');
            }

            $storage = $file_storage->functions[$function_id];

            /** @var PhpParser\Node\Expr\Closure $this->function */
            $this->function->inferredType = new Type\Union([
                new Type\Atomic\Fn(
                    'Closure',
                    $storage->params,
                    $storage->return_type ?: Type::getMixed()
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

        // this increases memory, so only do it if running under this flag
        if ($project_checker->infer_types_from_usage) {
            $this->statements_checker = $statements_checker;
        }

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
                    $this->getMethodId()
                );
            } else {
                $param_type = Type::getMixed();
            }

            $context->vars_in_scope['$' . $function_param->name] = $param_type;
            $context->vars_possibly_in_scope['$' . $function_param->name] = true;

            if (!$function_param->location) {
                continue;
            }

            $parser_param = $this->function->getParams()[$offset];

            if ($signature_type) {
                if (!TypeChecker::isContainedBy(
                    $project_checker,
                    $param_type,
                    $signature_type
                )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidDocblock(
                            'Parameter $' . $function_param->name . ' has wrong type \'' . $param_type .
                                '\', should be \'' . $signature_type . '\'',
                            $function_param->location
                        ),
                        $storage->suppressed_issues
                    )) {
                        return false;
                    }

                    continue;
                }
            }

            if ($parser_param->default) {
                $default_type = StatementsChecker::getSimpleType($parser_param->default);

                if ($default_type &&
                    !TypeChecker::isContainedBy(
                        $project_checker,
                        $default_type,
                        $param_type
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidParamDefault(
                            'Default value for argument ' . ($offset + 1) . ' of method ' . $cased_method_id .
                                ' does not match the given type ' . $param_type,
                            $function_param->location
                        )
                    )) {
                        // fall through
                    }
                }
            }

            if ($template_types) {
                $substituted_type = clone $param_type;
                $generic_types = [];
                $substituted_type->replaceTemplateTypes($template_types, $generic_types, null);
                $substituted_type->check($this->source, $function_param->location, $this->suppressed_issues, [], false);
            } else {
                $param_type->check($this->source, $function_param->location, $this->suppressed_issues, [], false);
            }

            if ($this->getFileChecker()->project_checker->collect_references) {
                if ($function_param->location !== $function_param->signature_location &&
                    $function_param->signature_location &&
                    $function_param->signature_type
                ) {
                    $function_param->signature_type->check(
                        $this->source,
                        $function_param->signature_location,
                        $this->suppressed_issues,
                        [],
                        false
                    );
                }
            }

            if ($function_param->by_ref && !$param_type->isMixed()) {
                $context->byref_constraints['$' . $function_param->name] = new \Psalm\ReferenceConstraint($param_type);
            }

            if ($function_param->by_ref) {
                // register by ref params as having been used, to avoid false positives
                // @todo change the assignment analysis *just* for byref params
                // so that we don't have to do this
                $context->hasVariable('$' . $function_param->name);
            }

            $statements_checker->registerVariable(
                '$' . $function_param->name,
                $function_param->location
            );
        }

        if ($storage->return_type && $storage->signature_return_type && $storage->return_type_location) {
            if (!TypeChecker::isContainedBy(
                $project_checker,
                $storage->return_type,
                $storage->signature_return_type
            )
            ) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        'Docblock has incorrect return type \'' . $storage->return_type .
                            '\', should be \'' . $storage->signature_return_type . '\'',
                        $storage->return_type_location
                    ),
                    $storage->suppressed_issues
                )) {
                    return false;
                }
            }
        }

        $statements_checker->analyze($function_stmts, $context, null, $global_context);

        foreach ($storage->params as $offset => $function_param) {
            $signature_type = $function_param->signature_type;

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

                IssueBuffer::accepts(
                    new UntypedParam(
                        'Parameter $' . $function_param->name . ' has no provided type' . $infer_text,
                        $function_param->location
                    ),
                    $storage->suppressed_issues
                );
            }
        }

        if ($this->function instanceof Closure) {
            $closure_yield_types = [];

            $this->verifyReturnType(
                $project_checker,
                $storage->return_type,
                $this->source->getFQCLN(),
                $storage->return_type_location
            );

            if (!$storage->return_type || $storage->return_type->isMixed()) {
                $closure_yield_types = [];
                $closure_return_types = EffectsAnalyser::getReturnTypes(
                    $this->function->stmts,
                    $closure_yield_types,
                    $ignore_nullable_issues,
                    true
                );

                if ($closure_return_types && $this->function->inferredType) {
                    /** @var Type\Atomic\Fn */
                    $closure_atomic = $this->function->inferredType->types['Closure'];
                    $closure_atomic->return_type = new Type\Union($closure_return_types);
                }
            }
        }

        if ($context->collect_references &&
            !$this->getFileChecker()->project_checker->find_references_to &&
            $context->check_variables
        ) {
            foreach ($context->vars_possibly_in_scope as $var_name => $_) {
                if (strpos($var_name, '->') === false &&
                    $var_name !== '$this' &&
                    strpos($var_name, '::$') === false &&
                    strpos($var_name, '[') === false &&
                    $var_name !== '$_'
                ) {
                    $original_location = $statements_checker->getFirstAppearance($var_name);

                    if (!isset($context->referenced_vars[$var_name]) && $original_location) {
                        if (!isset($storage->param_types[substr($var_name, 1)]) ||
                            !$storage instanceof MethodStorage ||
                            $storage->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
                        ) {
                            if (IssueBuffer::accepts(
                                new UnusedVariable(
                                    'Variable ' . $var_name . ' is never referenced',
                                    $original_location
                                ),
                                $this->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new PossiblyUnusedVariable(
                                    'Variable ' . $var_name . ' is never referenced in this method',
                                    $original_location
                                ),
                                $this->getSuppressedIssues()
                            )) {
                                // fall through
                            }
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
            return ($this->source->getNamespace() ? strtolower($this->source->getNamespace()) . '\\' : '') .
                strtolower($this->function->name);
        }

        return $this->getFilePath() . ':' . $this->function->getLine() . ':' . 'closure';
    }

    /**
     * @return FunctionLikeStorage
     */
    public function getFunctionLikeStorage()
    {
        $function_id = $this->getMethodId();

        $project_checker = $this->getFileChecker()->project_checker;

        if (strpos($function_id, '::')) {
            $declaring_method_id = MethodChecker::getDeclaringMethodId($project_checker, $function_id);

            if (!$declaring_method_id) {
                throw new \UnexpectedValueException('The declaring method of ' . $function_id . ' should not be null');
            }

            return MethodChecker::getStorage($project_checker, $declaring_method_id);
        }

        if (!$this->statements_checker) {
            throw new \UnexpectedValueException(
                '$this->statements_checker should be defined when getting storage for ' . $function_id
            );
        }

        return FunctionChecker::getStorage($this->statements_checker, $function_id);
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
     * @param   Type\Union|null     $return_type
     * @param   string              $fq_class_name
     * @param   CodeLocation|null   $return_type_location
     * @param   CodeLocation|null   $secondary_return_type_location
     *
     * @return  false|null
     */
    public function verifyReturnType(
        ProjectChecker $project_checker,
        Type\Union $return_type = null,
        $fq_class_name = null,
        CodeLocation $return_type_location = null,
        CodeLocation $secondary_return_type_location = null
    ) {
        if (!$this->function->getStmts() &&
            ($this->function instanceof ClassMethod &&
                ($this->getSource() instanceof InterfaceChecker || $this->function->isAbstract())
            )
        ) {
            return null;
        }

        $is_to_string = $this->function instanceof ClassMethod && strtolower($this->function->name) === '__tostring';

        if ($this->function instanceof ClassMethod &&
            substr($this->function->name, 0, 2) === '__' &&
            !$is_to_string
        ) {
            // do not check __construct, __set, __get, __call etc.
            return null;
        }

        $method_id = (string)$this->getMethodId();

        $cased_method_id = null;

        if ($this instanceof MethodChecker) {
            $cased_method_id = MethodChecker::getCasedMethodId($this->file_checker->project_checker, $method_id);
        } elseif ($this->function instanceof Function_) {
            $cased_method_id = $this->function->name;
        }

        if (!$return_type_location) {
            $return_type_location = new CodeLocation($this, $this->function, null, true);
        }

        $inferred_yield_types = [];
        $inferred_return_types = EffectsAnalyser::getReturnTypes(
            $this->function->getStmts(),
            $inferred_yield_types,
            $ignore_nullable_issues,
            true
        );

        $inferred_return_type = $inferred_return_types ? Type::combineTypes($inferred_return_types) : Type::getVoid();
        $inferred_yield_type = $inferred_yield_types ? Type::combineTypes($inferred_yield_types) : null;

        if ($inferred_yield_type) {
            $inferred_return_type = $inferred_yield_type;
        }

        if (!$return_type && !Config::getInstance()->add_void_docblocks && $inferred_return_type->isVoid()) {
            return null;
        }

        $project_checker = $this->getFileChecker()->project_checker;

        $inferred_return_type = TypeChecker::simplifyUnionType(
            $project_checker,
            ExpressionChecker::fleshOutType(
                $project_checker,
                $inferred_return_type,
                $this->source->getFQCLN(),
                ''
            )
        );

        if (!$return_type && !$project_checker->update_docblocks && !$is_to_string) {
            if ($this->function instanceof Closure) {
                if (IssueBuffer::accepts(
                    new MissingClosureReturnType(
                        'Closure does not have a return type, expecting ' . $inferred_return_type,
                        new CodeLocation($this, $this->function, null, true)
                    ),
                    $this->suppressed_issues
                )) {
                    // fall through
                }

                return null;
            }

            if (IssueBuffer::accepts(
                new MissingReturnType(
                    'Method ' . $cased_method_id . ' does not have a return type' .
                      (!$inferred_return_type->isMixed() ? ', expecting ' . $inferred_return_type : ''),
                    new CodeLocation($this, $this->function, null, true)
                ),
                $this->suppressed_issues
            )) {
                // fall through
            }

            return null;
        }

        if ($is_to_string) {
            if (!$inferred_return_type->isMixed() && (string)$inferred_return_type !== 'string') {
                if (IssueBuffer::accepts(
                    new InvalidToString(
                        '__toString methods must return a string, ' . $inferred_return_type . ' returned',
                        $secondary_return_type_location ?: $return_type_location
                    )
                )) {
                    return false;
                }
            }

            if (!$return_type && !$project_checker->update_docblocks) {
                return null;
            }
        }

        if (!$return_type) {
            if ($inferred_return_type && !$inferred_return_type->isMixed()) {
                $this->addDocblockReturnType($project_checker, $inferred_return_type);
            }

            return null;
        }

        // passing it through fleshOutTypes eradicates errant $ vars
        $declared_return_type = ExpressionChecker::fleshOutType(
            $project_checker,
            $return_type,
            $fq_class_name ?: $this->source->getFQCLN(),
            $method_id
        );

        if (!$inferred_return_types && !$inferred_yield_types) {
            if ($declared_return_type->isVoid()) {
                return null;
            }

            if (ScopeChecker::onlyThrows($this->function->getStmts())) {
                // if there's a single throw statement, it's presumably an exception saying this method is not to be
                // used
                return null;
            }

            if (IssueBuffer::accepts(
                new InvalidReturnType(
                    'No return statements were found for method ' . $cased_method_id .
                        ' but return type \'' . $declared_return_type . '\' was expected',
                    $secondary_return_type_location ?: $return_type_location
                )
            )) {
                return false;
            }

            return null;
        }

        if (!$declared_return_type->isMixed()) {
            $declared_return_type->check(
                $this,
                $secondary_return_type_location ?: $return_type_location,
                $this->getSuppressedIssues(),
                [],
                false
            );
        }

        if ($inferred_return_type && !$declared_return_type->isMixed()) {
            if ($inferred_return_type->isVoid() && $declared_return_type->isVoid()) {
                return null;
            }

            if ($inferred_return_type->isMixed() || $inferred_return_type->isEmpty()) {
                if (IssueBuffer::accepts(
                    new MixedInferredReturnType(
                        'Could not verify return type \'' . $declared_return_type . '\' for ' .
                            $cased_method_id,
                        $secondary_return_type_location ?: $return_type_location
                    ),
                    $this->suppressed_issues
                )) {
                    return false;
                }

                return null;
            }

            if (!TypeChecker::isContainedBy(
                $this->source->getFileChecker()->project_checker,
                $inferred_return_type,
                $declared_return_type,
                $ignore_nullable_issues,
                false,
                $has_scalar_match,
                $type_coerced
            )) {
                if ($project_checker->update_docblocks) {
                    if (!in_array('InvalidReturnType', $this->suppressed_issues, true)) {
                        $this->addDocblockReturnType($project_checker, $inferred_return_type);
                    }

                    return null;
                }

                // is the declared return type more specific than the inferred one?
                if ($type_coerced) {
                    if (IssueBuffer::accepts(
                        new MoreSpecificReturnType(
                            'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                                ' is more specific than the inferred return type \'' . $inferred_return_type . '\'',
                            $secondary_return_type_location ?: $return_type_location
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidReturnType(
                            'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                                ' is incorrect, got \'' . $inferred_return_type . '\'',
                            $secondary_return_type_location ?: $return_type_location
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }
                }
            } elseif (!$inferred_return_type->isNullable() && $declared_return_type->isNullable()) {
                if ($project_checker->update_docblocks) {
                    if (!in_array('InvalidReturnType', $this->suppressed_issues, true)) {
                        $this->addDocblockReturnType($project_checker, $inferred_return_type);
                    }

                    return null;
                }

                if (IssueBuffer::accepts(
                    new LessSpecificReturnType(
                        'The inferred return type \'' . $inferred_return_type . '\' for ' . $cased_method_id .
                            ' is more specific than the declared return type \'' . $declared_return_type . '\'',
                        $secondary_return_type_location ?: $return_type_location
                    ),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param Type\Union $inferred_return_type
     *
     * @return void
     */
    private function addDocblockReturnType(ProjectChecker $project_checker, Type\Union $inferred_return_type)
    {
        $manipulator = FunctionDocblockManipulator::getForFunction(
            $project_checker,
            $this->source->getFilePath(),
            $this->getMethodId(),
            $this->function
        );

        $manipulator->setDocblockReturnType(
            $inferred_return_type->toNamespacedString(
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                false
            ),
            $inferred_return_type->toNamespacedString(
                $this->source->getAliasedClassesFlipped(),
                $this->source->getFQCLN(),
                true
            )
        );
    }

    /**
     * @param  \ReflectionParameter $param
     *
     * @return FunctionLikeParameter
     */
    protected static function getReflectionParamData(\ReflectionParameter $param)
    {
        $param_type_string = null;

        if ($param->isArray()) {
            $param_type_string = 'array';
        } else {
            $param_class = null;

            try {
                /** @var \ReflectionClass */
                $param_class = $param->getClass();
            } catch (\ReflectionException $e) {
                // do nothing
            }

            if ($param_class) {
                $param_type_string = (string)$param_class->getName();
            }
        }

        $is_nullable = false;

        $is_optional = (bool)$param->isOptional();

        try {
            $is_nullable = $param->getDefaultValue() === null;

            if ($param_type_string && $is_nullable) {
                $param_type_string .= '|null';
            }
        } catch (\ReflectionException $e) {
            // do nothing
        }

        $param_name = (string)$param->getName();
        $param_type = $param_type_string ? Type::parseString($param_type_string) : Type::getMixed();

        return new FunctionLikeParameter(
            $param_name,
            (bool)$param->isPassedByReference(),
            $param_type,
            null,
            $is_optional,
            $is_nullable,
            $param->isVariadic()
        );
    }

    /**
     * @param  string                       $return_type
     * @param  Aliases                      $aliases
     * @param  array<string, string>|null   $template_types
     *
     * @return string
     */
    public static function fixUpLocalType(
        $return_type,
        Aliases $aliases,
        array $template_types = null
    ) {
        if (strpos($return_type, '[') !== false) {
            $return_type = Type::convertSquareBrackets($return_type);
        }

        $return_type_tokens = Type::tokenize($return_type);

        foreach ($return_type_tokens as $i => &$return_type_token) {
            if (in_array($return_type_token, ['<', '>', '|', '?', ',', '{', '}', ':'], true)) {
                continue;
            }

            if (isset($return_type_tokens[$i + 1]) && $return_type_tokens[$i + 1] === ':') {
                continue;
            }

            $return_type_token = Type::fixScalarTerms($return_type_token);

            if ($return_type_token[0] === strtoupper($return_type_token[0]) &&
                !isset($template_types[$return_type_token])
            ) {
                if ($return_type_token[0] === '$') {
                    if ($return_type === '$this') {
                        $return_type_token = 'static';
                    }

                    continue;
                }

                $return_type_token = ClassLikeChecker::getFQCLNFromString(
                    $return_type_token,
                    $aliases
                );
            }
        }

        return implode('', $return_type_tokens);
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

        if ($fq_class_name && ClassLikeChecker::isUserDefined($project_checker, $fq_class_name)) {
            $method_params = MethodChecker::getMethodParams($project_checker, $method_id);

            return $method_params;
        }

        $declaring_method_id = MethodChecker::getDeclaringMethodId($project_checker, $method_id);

        if (FunctionChecker::inCallMap($declaring_method_id ?: $method_id)) {
            $function_param_options = FunctionChecker::getParamsFromCallMap($declaring_method_id ?: $method_id);

            if ($function_param_options === null) {
                throw new \UnexpectedValueException(
                    'Not expecting $function_param_options to be null for ' . $method_id
                );
            }

            return self::getMatchingParamsFromCallMapOptions($project_checker, $function_param_options, $args);
        }

        return MethodChecker::getMethodParams($project_checker, $method_id);
    }

    /**
     * @param  string                           $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     *
     * @return array<int, FunctionLikeParameter>
     */
    public static function getFunctionParamsFromCallMapById(ProjectChecker $project_checker, $method_id, array $args)
    {
        $function_param_options = FunctionChecker::getParamsFromCallMap($method_id);

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

                if (TypeChecker::isContainedBy($project_checker, $arg->value->inferredType, $param_type)) {
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

    public function getFileChecker()
    {
        return $this->file_checker;
    }
}
