<?php
namespace Psalm\Checker;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Config;
use Psalm\Context;
use Psalm\EffectsAnalyser;
use Psalm\Exception\DocblockParseException;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidParamDefault;
use Psalm\Issue\InvalidReturnType;
use Psalm\Issue\InvalidToString;
use Psalm\Issue\MethodSignatureMismatch;
use Psalm\Issue\MisplacedRequiredParam;
use Psalm\Issue\MissingClosureReturnType;
use Psalm\Issue\MissingReturnType;
use Psalm\Issue\MixedInferredReturnType;
use Psalm\Issue\MoreSpecificReturnType;
use Psalm\Issue\OverriddenMethodAccess;
use Psalm\Issue\PossiblyUnusedVariable;
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

    /**
     * @var array<string, array<string, Type\Union>>
     */
    protected $return_vars_in_scope = [];

    /**
     * @var array<string, array<string,bool>>
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
        $this->suppressed_issues = $source->getSuppressedIssues();
    }

    /**
     * @param Context       $context
     * @param Context|null  $global_context
     * @param bool          $add_mutations  whether or not to add mutations to this method
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

        if ($this->function instanceof ClassMethod) {
            $real_method_id = (string)$this->getMethodId();

            $method_id = (string)$this->getMethodId($context->self);

            if ($add_mutations) {
                $hash = $this->getMethodId() . json_encode([
                    $context->vars_in_scope,
                        $context->vars_possibly_in_scope
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

            $declaring_method_id = (string)MethodChecker::getDeclaringMethodId($method_id);

            if ($declaring_method_id !== $real_method_id) {
                // this trait method has been overridden, so we don't care about it
                return;
            }

            $fq_class_name = (string)$context->self;

            $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

            $storage = MethodChecker::getStorage($declaring_method_id);

            $cased_method_id = $fq_class_name . '::' . $storage->cased_name;

            $implemented_method_ids = MethodChecker::getOverriddenMethodIds($method_id);

            if ($implemented_method_ids) {
                $have_emitted = false;

                foreach ($implemented_method_ids as $implemented_method_id) {
                    if ($have_emitted) {
                        break;
                    }

                    if ($this->function->name === '__construct') {
                        continue;
                    }

                    list($implemented_fq_class_name) = explode('::', $implemented_method_id);

                    $class_storage = ClassLikeChecker::$storage[strtolower($implemented_fq_class_name)];

                    $implemented_storage = MethodChecker::getStorage($implemented_method_id);

                    if ($implemented_storage->visibility < $storage->visibility) {
                        $parent_method_id = MethodChecker::getCasedMethodId($implemented_method_id);
                        if (IssueBuffer::accepts(
                            new OverriddenMethodAccess(
                                'Method ' . $cased_method_id .' has different access level than ' . $parent_method_id,
                                new CodeLocation($this, $this->function, true)
                            )
                        )) {
                            return false;
                        }

                        continue;
                    }

                    $implemented_params = $implemented_storage->params;

                    foreach ($implemented_params as $i => $implemented_param) {
                        if (!isset($storage->params[$i])) {
                            $parent_method_id = MethodChecker::getCasedMethodId($implemented_method_id);

                            if (IssueBuffer::accepts(
                                new MethodSignatureMismatch(
                                    'Method ' . $cased_method_id .' has fewer arguments than parent method ' .
                                        $parent_method_id,
                                    new CodeLocation($this, $this->function, true)
                                )
                            )) {
                                return false;
                            }

                            $have_emitted = true;
                            break;
                        }

                        if ($class_storage->user_defined &&
                            (string)$storage->params[$i]->signature_type !== (string)$implemented_param->signature_type
                        ) {
                            $cased_method_id = MethodChecker::getCasedMethodId((string)$this->getMethodId());
                            $parent_method_id = MethodChecker::getCasedMethodId($implemented_method_id);

                            if (IssueBuffer::accepts(
                                new MethodSignatureMismatch(
                                    'Argument ' . ($i + 1) . ' of ' . $cased_method_id .' has wrong type \'' .
                                        $storage->params[$i]->signature_type . '\', expecting \'' .
                                        $implemented_param->signature_type . '\' as defined by ' .
                                        $parent_method_id,
                                    $storage->params[$i]->code_location ?: new CodeLocation($this, $this->function, true)
                                )
                            )) {
                                return false;
                            }

                            $have_emitted = true;
                            break;
                        }

                        if (!$class_storage->user_defined &&
                            !$implemented_param->type->isMixed() &&
                            (string)$storage->params[$i]->type !== (string)$implemented_param->type
                        ) {
                            $cased_method_id = MethodChecker::getCasedMethodId((string)$this->getMethodId());
                            $parent_method_id = MethodChecker::getCasedMethodId($implemented_method_id);

                            if (IssueBuffer::accepts(
                                new MethodSignatureMismatch(
                                    'Argument ' . ($i + 1) . ' of ' . $cased_method_id .' has wrong type \'' .
                                        $storage->params[$i]->type . '\', expecting \'' .
                                        $implemented_param->type . '\' as defined by ' .
                                        $parent_method_id,
                                    $storage->params[$i]->code_location ?: new CodeLocation($this, $this->function, true)
                                )
                            )) {
                                return false;
                            }

                            $have_emitted = true;
                            break;
                        }
                    }

                    if ($storage->cased_name !== '__construct' &&
                        $storage->required_param_count > $implemented_storage->required_param_count
                    ) {
                        $parent_method_id = MethodChecker::getCasedMethodId($implemented_method_id);

                        if (IssueBuffer::accepts(
                            new MethodSignatureMismatch(
                                'Method ' . $cased_method_id .' has more arguments than parent method ' .
                                    $parent_method_id,
                                new CodeLocation($this, $this->function, true)
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
            $storage = FileChecker::$storage[$this->source->getFilePath()]->functions[(string)$this->getMethodId()];

            $cased_method_id = $this->function->name;
        } else { // Closure
            $storage = self::register($this->function, $this->getSource());

            /** @var PhpParser\Node\Expr\Closure $this->function */
            $this->function->inferredType = new Type\Union([
                new Type\Atomic\Fn(
                    'Closure',
                    $storage->params,
                    $storage->return_type ?: Type::getMixed()
                )
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
            $param_type = clone $function_param->type;

            $param_type = ExpressionChecker::fleshOutTypes(
                $param_type,
                [],
                $context->self,
                $this->getMethodId()
            );

            if (!$function_param->code_location) {
                throw new \UnexpectedValueException('We should know where this code is');
            }

            $parser_param = $this->function->getParams()[$offset];

            if ($parser_param->default) {
                $default_type = StatementsChecker::getSimpleType($parser_param->default);

                if ($default_type &&
                    !TypeChecker::isContainedBy(
                        $default_type,
                        $param_type,
                        $statements_checker->getFileChecker()
                    )
                ) {
                    $method_id = $this->getMethodId();

                    if (IssueBuffer::accepts(
                        new InvalidParamDefault(
                            'Default value for argument ' . ($offset + 1) . ' of method ' . $cased_method_id .
                                ' does not match the given type ' . $param_type,
                            $function_param->code_location
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
                $substituted_type->check($this->source, $function_param->code_location, $this->suppressed_issues);
            } else {
                $param_type->check($this->source, $function_param->code_location, $this->suppressed_issues);
            }

            $context->vars_in_scope['$' . $function_param->name] = $param_type;
            $context->vars_possibly_in_scope['$' . $function_param->name] = true;

            if ($function_param->by_ref) {
                // register by ref params as having been used, to avoid false positives
                // @todo change the assignment analysis *just* for byref params
                // so that we don't have to do this
                $context->hasVariable('$' . $function_param->name);
            }

            $statements_checker->registerVariable(
                '$' . $function_param->name,
                $function_param->code_location
            );
        }

        $statements_checker->analyze($function_stmts, $context, null, $global_context);

        if ($this->function instanceof Closure) {
            $closure_yield_types = [];

            $this->verifyReturnType(
                false,
                $storage->return_type,
                $this->source->getFQCLN(),
                $storage->return_type_location
            );

            if (!$storage->return_type || $storage->return_type->isMixed()) {
                $closure_yield_types = [];
                $closure_return_types = EffectsAnalyser::getReturnTypes(
                    $this->function->stmts,
                    $closure_yield_types,
                    true
                );

                if ($closure_return_types && $this->function->inferredType) {
                    /** @var Type\Atomic\Fn */
                    $closure_atomic = $this->function->inferredType->types['Closure'];
                    $closure_atomic->return_type = new Type\Union($closure_return_types);
                }
            }
        }

        if ($context->count_references && $context->check_variables) {
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
                    $context->vars_possibly_in_scope
                ];
            }
        }

        return null;
    }

    /**
     * @param  Closure|Function_|ClassMethod    $function
     * @param  StatementsSource                 $source
     * @return FunctionLikeStorage
     */
    public static function register(
        $function,
        StatementsSource $source
    ) {
        $namespace = $source->getNamespace();

        $class_storage = null;

        if ($function instanceof PhpParser\Node\Stmt\Function_) {
            $cased_function_id = ($namespace ? $namespace . '\\' : '') . $function->name;
            $function_id = strtolower($cased_function_id);

            $project_checker = $source->getFileChecker()->project_checker;

            if ($project_checker->register_global_functions) {
                $storage = FunctionChecker::$stubbed_functions[$function_id] = new FunctionLikeStorage();
            } else {
                $file_storage = FileChecker::$storage[$source->getFilePath()];

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id];
                }

                $storage = $file_storage->functions[$function_id] = new FunctionLikeStorage();
            }
        } elseif ($function instanceof PhpParser\Node\Stmt\ClassMethod) {
            $fq_class_name = (string)$source->getFQCLN();

            $function_id = $fq_class_name . '::' . strtolower($function->name);
            $cased_function_id = $fq_class_name . '::' . $function->name;

            $fq_class_name_lower = strtolower($fq_class_name);

            if (!isset(ClassLikeChecker::$storage[$fq_class_name_lower])) {
                throw new \UnexpectedValueException('$class_storage cannot be empty for ' . $function_id);
            }

            $class_storage = ClassLikeChecker::$storage[$fq_class_name_lower];

            if (isset($class_storage->methods[strtolower($function->name)])) {
                throw new \InvalidArgumentException('Cannot re-register ' . $function_id);
            }

            $storage = $class_storage->methods[strtolower($function->name)] = new MethodStorage();

            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (strtolower((string)$function->name) === strtolower($class_name)) {
                MethodChecker::setDeclaringMethodId($fq_class_name . '::__construct', $function_id);
                MethodChecker::setAppearingMethodId($fq_class_name . '::__construct', $function_id);
            }

            $class_storage->declaring_method_ids[strtolower($function->name)] = $function_id;
            $class_storage->appearing_method_ids[strtolower($function->name)] = $function_id;

            if (!isset($class_storage->overridden_method_ids[strtolower($function->name)])) {
                $class_storage->overridden_method_ids[strtolower($function->name)] = [];
            }

            /** @var bool */
            $storage->is_static = $function->isStatic();

            if ($function->isPrivate()) {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PRIVATE;
            } elseif ($function->isProtected()) {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PROTECTED;
            } else {
                $storage->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;
            }
        } else {
            $function_id = $cased_function_id = 'closure';

            $storage = new FunctionLikeStorage();
        }

        if ($function instanceof ClassMethod || $function instanceof Function_) {
            $storage->cased_name = $function->name;
        }

        $storage->location = new CodeLocation($source, $function, true);
        $storage->namespace = $source->getNamespace();

        $required_param_count = 0;
        $i = 0;
        $has_optional_param = false;

        /** @var PhpParser\Node\Param $param */
        foreach ($function->getParams() as $param) {
            $param_array = self::getTranslatedParam($param, $source);

            if (isset($storage->param_types[$param_array->name])) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Duplicate param $' . $param->name . ' in docblock for ' . $cased_function_id,
                        new CodeLocation($source, $param, true)
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $storage->param_types[$param_array->name] = $param_array->type;
            $storage->params[] = $param_array;

            if (!$param_array->is_optional) {
                $required_param_count = $i + 1;

                if (!$param->variadic && $has_optional_param) {
                    if (IssueBuffer::accepts(
                        new MisplacedRequiredParam(
                            'Required param $' . $param->name . ' should come before any optional params in ' .
                            $cased_function_id,
                            new CodeLocation($source, $param, true)
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } else {
                $has_optional_param = true;
            }

            $i++;
        }

        $storage->required_param_count = $required_param_count;

        if ($function->stmts) {
            // look for constant declarations
            foreach ($function->stmts as $stmt) {
                if ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
                    $stmt->name instanceof PhpParser\Node\Name &&
                    $stmt->name->parts === ['define']
                ) {
                    $first_arg_value = isset($stmt->args[0]) ? $stmt->args[0]->value : null;
                    $second_arg_value = isset($stmt->args[1]) ? $stmt->args[1]->value : null;

                    if ($first_arg_value instanceof PhpParser\Node\Scalar\String_ && $second_arg_value) {
                        $storage->defined_constants[$first_arg_value->value] =
                            StatementsChecker::getSimpleType($second_arg_value) ?: Type::getMixed();
                    }
                }
            }
        }

        $config = Config::getInstance();

        if ($parser_return_type = $function->getReturnType()) {
            $suffix = '';

            if ($parser_return_type instanceof PhpParser\Node\NullableType) {
                $suffix = '|null';
                $parser_return_type = $parser_return_type->type;
            }

            if (is_string($parser_return_type)) {
                $return_type_string = $parser_return_type . $suffix;
            } else {
                $return_type_fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $parser_return_type,
                    $source
                );

                $return_type_string = $return_type_fq_class_name . $suffix;
            }

            $storage->return_type = Type::parseString($return_type_string);
            $storage->return_type_location = new CodeLocation(
                $source,
                $function,
                false,
                self::RETURN_TYPE_REGEX
            );
        }

        $docblock_info = null;
        $doc_comment = $function->getDocComment();

        if (!$doc_comment) {
            return $storage;
        }

        try {
            $docblock_info = CommentChecker::extractFunctionDocblockInfo(
                (string)$doc_comment,
                $doc_comment->getLine()
            );
        } catch (DocblockParseException $e) {
            if (IssueBuffer::accepts(
                new InvalidDocblock(
                    $e->getMessage() . ' in docblock for ' . $cased_function_id,
                    new CodeLocation($source, $function, true)
                )
            )) {
                // fall through
            }
        }

        if (!$docblock_info) {
            return $storage;
        }

        if ($docblock_info->deprecated) {
            $storage->deprecated = true;
        }

        if ($docblock_info->variadic) {
            $storage->variadic = true;
        }

        $storage->suppressed_issues = $docblock_info->suppress;

        if (!$config->use_docblock_types) {
            return $storage;
        }

        $template_types = $class_storage && $class_storage->template_types ? $class_storage->template_types : null;

        if ($docblock_info) {
            if ($docblock_info->template_types) {
                $storage->template_types = [];

                foreach ($docblock_info->template_types as $template_type) {
                    if (count($template_type) === 3) {
                        $as_type_string = ClassLikeChecker::getFQCLNFromString($template_type[2], $source);
                        $storage->template_types[$template_type[0]] = $as_type_string;
                    } else {
                        $storage->template_types[$template_type[0]] = 'mixed';
                    }
                }

                $template_types = array_merge($template_types ?: [], $storage->template_types);
            }

            if ($docblock_info->template_typeofs) {
                $storage->template_typeof_params = [];

                foreach ($docblock_info->template_typeofs as $template_typeof) {
                    foreach ($storage->params as $i => $param) {
                        if ($param->name === $template_typeof['param_name']) {
                            $storage->template_typeof_params[$i] = $template_typeof['template_type'];
                            break;
                        }
                    }
                }
            }
        }

        if ($docblock_info->return_type) {
            $storage->has_template_return_type =
                $template_types !== null &&
                count(array_intersect(Type::tokenize($docblock_info->return_type), array_keys($template_types))) > 0;

            $docblock_return_type = Type::parseString(
                self::fixUpLocalType(
                    (string)$docblock_info->return_type,
                    $source,
                    $template_types
                )
            );

            if (!$storage->return_type_location) {
                $storage->return_type_location = new CodeLocation($source, $function, true);
            }

            if ($storage->return_type &&
                !TypeChecker::hasIdenticalTypes(
                    $storage->return_type,
                    $docblock_return_type,
                    $source->getFileChecker()
                )
            ) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        'Docblock return type does not match method return type for ' . $cased_function_id,
                        new CodeLocation($source, $function, true)
                    )
                )) {
                    // fall through
                }
            } else {
                $storage->return_type = $docblock_return_type;
            }

            if ($docblock_info->return_type_line_number) {
                $storage->return_type_location->setCommentLine($docblock_info->return_type_line_number);
            }
        }

        if ($docblock_info->params) {
            self::improveParamsFromDocblock(
                $storage,
                $docblock_info->params,
                $template_types,
                $source,
                $source->getFQCLN(),
                new CodeLocation($source, $function, true)
            );
        }

        return $storage;
    }

    /**
     * Adds return types for the given function
     *
     * @param   string  $return_type
     * @param   Context $context
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
     * @param string|null $context_self
     * @return null|string
     */
    public function getMethodId($context_self = null)
    {
        if ($this->function instanceof ClassMethod) {
            $function_name = (string)$this->function->name;

            return ($context_self ?: $this->source->getFQCLN()) . '::' . strtolower($function_name);
        }

        if ($this->function instanceof Function_) {
            return ($this->source->getNamespace() ? strtolower($this->source->getNamespace()) . '\\' : '') . strtolower($this->function->name);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        if ($this->source instanceof NamespaceChecker || $this->source instanceof FileChecker || $this->source instanceof ClassLikeChecker) {
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
     * @param   bool                $update_docblock
     * @param   Type\Union|null     $return_type
     * @param   string              $fq_class_name
     * @param   CodeLocation|null   $return_type_location
     * @param   CodeLocation|null   $secondary_return_type_location
     * @return  false|null
     */
    public function verifyReturnType(
        $update_docblock = false,
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
            $cased_method_id = MethodChecker::getCasedMethodId($method_id);
        } elseif ($this->function instanceof Function_) {
            $cased_method_id = $this->function->name;
        }

        if (!$return_type_location) {
            $return_type_location = new CodeLocation($this, $this->function, true);
        }

        $inferred_yield_types = [];
        $inferred_return_types = EffectsAnalyser::getReturnTypes(
            $this->function->getStmts(),
            $inferred_yield_types,
            true
        );

        $inferred_return_type = $inferred_return_types ? Type::combineTypes($inferred_return_types) : Type::getVoid();
        $inferred_yield_type = $inferred_yield_types ? Type::combineTypes($inferred_yield_types) : null;

        $inferred_generator_return_type = null;

        if ($inferred_yield_type) {
            $inferred_generator_return_type = $inferred_return_type;
            $inferred_return_type = $inferred_yield_type;
        }

        if (!$return_type && !Config::getInstance()->add_void_docblocks && $inferred_return_type->isVoid()) {
            return null;
        }

        $inferred_return_type = TypeChecker::simplifyUnionType(
            ExpressionChecker::fleshOutTypes(
                $inferred_return_type,
                [],
                $this->source->getFQCLN(),
                ''
            ),
            $this->getFileChecker()
        );

        if (!$return_type && !$update_docblock && !$is_to_string) {
            if ($this->function instanceof Closure) {
                if (IssueBuffer::accepts(
                    new MissingClosureReturnType(
                        'Closure does not have a return type, expecting ' . $inferred_return_type,
                        new CodeLocation($this, $this->function, true)
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
                    new CodeLocation($this, $this->function, true)
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

            if (!$return_type && !$update_docblock) {
                return null;
            }
        }

        if (!$return_type) {
            if ($inferred_return_type && !$inferred_return_type->isMixed()) {
                $this->addDocblockReturnType($inferred_return_type);
            }

            return null;
        }

        // passing it through fleshOutTypes eradicates errant $ vars
        $declared_return_type = ExpressionChecker::fleshOutTypes(
            $return_type,
            [],
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
                $inferred_return_type,
                $declared_return_type,
                $this->getFileChecker(),
                true,
                $has_scalar_match,
                $type_coerced
            )) {
                if ($update_docblock) {
                    if (!in_array('InvalidReturnType', $this->suppressed_issues)) {
                        $this->addDocblockReturnType($inferred_return_type);
                    }

                    return null;
                }

                // is the declared return type more specific than the inferred one?
                if ($type_coerced) {
                    if (IssueBuffer::accepts(
                        new MoreSpecificReturnType(
                            'The given return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
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
                            'The given return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                                ' is incorrect, got \'' . $inferred_return_type . '\'',
                            $secondary_return_type_location ?: $return_type_location
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }
                }
            } elseif ($inferred_return_type->isNullable() !== $declared_return_type->isNullable()) {
                if ($update_docblock) {
                    if (!in_array('InvalidReturnType', $this->suppressed_issues)) {
                        $this->addDocblockReturnType($inferred_return_type);
                    }

                    return null;
                }

                if (IssueBuffer::accepts(
                    new MoreSpecificReturnType(
                        'The given return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                            ' is more specific than the inferred return type \'' . $inferred_return_type . '\'',
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
     * @return void
     */
    private function addDocblockReturnType(Type\Union $inferred_return_type)
    {
        FileChecker::addDocblockReturnType(
            $this->source->getFileName(),
            $this->function->getLine(),
            (string)$this->function->getDocComment(),
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
     * @param  array<int, array{type:string,name:string,line_number:int}>  $docblock_params
     * @param  FunctionLikeStorage          $storage
     * @param  array<string, string>|null   $template_types
     * @param  StatementsSource             $source
     * @param  string|null                  $fq_class_name
     * @param  CodeLocation                 $code_location
     * @return false|null
     */
    protected static function improveParamsFromDocblock(
        FunctionLikeStorage $storage,
        array $docblock_params,
        $template_types,
        StatementsSource $source,
        $fq_class_name,
        CodeLocation $code_location
    ) {
        $docblock_param_vars = [];

        $base = $fq_class_name ? $fq_class_name . '::' : '';

        $cased_method_id = $base . $storage->cased_name;

        foreach ($docblock_params as $docblock_param) {
            $param_name = $docblock_param['name'];
            $line_number = $docblock_param['line_number'];
            $docblock_param_variadic = false;

            if (substr($param_name, 0, 3) === '...') {
                $docblock_param_variadic = true;
                $param_name = substr($param_name, 3);
            }

            $param_name = substr($param_name, 1);

            if (!isset($storage->param_types[$param_name])) {
                if ($line_number) {
                    $code_location->setCommentLine($line_number);
                }

                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        'Parameter $' . $param_name .' does not appear in the argument list for ' .
                            $cased_method_id,
                        $code_location
                    )
                )) {
                    return false;
                }

                continue;
            }

            $docblock_param_vars[$param_name] = true;

            $new_param_type = Type::parseString(
                self::fixUpLocalType(
                    $docblock_param['type'],
                    $source,
                    $template_types
                )
            );

            if ($docblock_param_variadic) {
                $new_param_type = new Type\Union([
                    new Type\Atomic\TArray([
                        Type::getInt(),
                        $new_param_type
                    ])
                ]);
            }

            $new_param_type->setFromDocblock();

            if (!$storage->param_types[$param_name]->isMixed()) {

                if (!TypeChecker::isContainedBy(
                    $new_param_type,
                    $storage->param_types[$param_name],
                    $source->getFileChecker()
                )
                ) {
                    $code_location->setCommentLine($line_number);
                    if (IssueBuffer::accepts(
                        new InvalidDocblock(
                            'Parameter $' . $param_name .' has wrong type \'' . $new_param_type . '\', should be \'' .
                                $storage->param_types[$param_name] . '\'',
                            $code_location
                        )
                    )) {
                        return false;
                    }

                    continue;
                }
            }

            foreach ($storage->params as $function_signature_param) {
                if ($function_signature_param->name === $param_name) {
                    $existing_param_type_nullable = $function_signature_param->is_nullable;

                    if ($existing_param_type_nullable && !$new_param_type->isNullable()) {
                        $new_param_type->types['null'] = new Type\Atomic\TNull();
                    }

                    $function_signature_param->signature_type = $function_signature_param->type;

                    if ((string)$function_signature_param->type !== (string)$new_param_type) {
                        $function_signature_param->type = $new_param_type;
                    }

                    break;
                }
            }
        }

        foreach ($storage->params as $function_signature_param) {
            if (!isset($docblock_param_vars[$function_signature_param->name]) &&
                $function_signature_param->code_location
            ) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        'Parameter $' . $function_signature_param->name .' does not appear in the docbock for ' .
                            $cased_method_id,
                        $function_signature_param->code_location
                    )
                )) {
                    return false;
                }

                continue;
            }
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Param $param
     * @param  StatementsSource     $source
     * @return FunctionLikeParameter
     */
    public static function getTranslatedParam(
        PhpParser\Node\Param $param,
        StatementsSource $source
    ) {
        $param_type = null;

        $is_nullable = $param->default !== null &&
            $param->default instanceof \PhpParser\Node\Expr\ConstFetch &&
            $param->default->name instanceof PhpParser\Node\Name &&
            strtolower($param->default->name->parts[0]) === 'null';

        $param_typehint = $param->type;

        if ($param_typehint instanceof PhpParser\Node\NullableType) {
            $is_nullable = true;
            $param_typehint = $param_typehint->type;
        }

        if ($param_typehint) {
            if (is_string($param_typehint)) {
                $param_type_string = $param_typehint;
            } elseif ($param_typehint instanceof PhpParser\Node\Name\FullyQualified) {
                $param_type_string = implode('\\', $param_typehint->parts);
            } elseif ($param_typehint->parts === ['self']) {
                $param_type_string = $source->getFQCLN();
            } else {
                $param_type_string = ClassLikeChecker::getFQCLNFromString(
                    implode('\\', $param_typehint->parts),
                    $source
                );
            }

            if ($param_type_string) {
                if ($is_nullable) {
                    $param_type_string .= '|null';
                }

                $param_type = Type::parseString($param_type_string);

                if ($param->variadic) {
                    $param_type = new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getInt(),
                            $param_type
                        ])
                    ]);
                }
            }
        } elseif ($param->variadic) {
            $param_type = new Type\Union([
                new Type\Atomic\TArray([
                    Type::getInt(),
                    Type::getMixed(),
                ])
            ]);
        }

        $is_optional = $param->default !== null;

        return new FunctionLikeParameter(
            $param->name,
            $param->byRef,
            $param_type ?: Type::getMixed(),
            new CodeLocation($source, $param, false, self::PARAM_TYPE_REGEX),
            $is_optional,
            $is_nullable,
            $param->variadic
        );
    }

    /**
     * @param  \ReflectionParameter $param
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
            $is_nullable
        );
    }

    /**
     * @param  string                       $return_type
     * @param  StatementsSource             $source
     * @param  array<string, string>|null   $template_types
     * @return string
     */
    public static function fixUpLocalType(
        $return_type,
        StatementsSource $source,
        array $template_types = null
    ) {
        if (strpos($return_type, '[') !== false) {
            $return_type = Type::convertSquareBrackets($return_type);
        }

        $return_type_tokens = Type::tokenize($return_type);

        foreach ($return_type_tokens as $i => &$return_type_token) {
            if ($return_type_token[0] === '\\') {
                $return_type_token = substr($return_type_token, 1);
                continue;
            }

            if (in_array($return_type_token, ['<', '>', '|', '?', ',', '{', '}', ':'])) {
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
                    $source
                );
            }
        }

        return implode('', $return_type_tokens);
    }

    /**
     * @param  string                           $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  FileChecker                      $file_checker
     * @return array<int, FunctionLikeParameter>
     */
    public static function getMethodParamsById($method_id, array $args, FileChecker $file_checker)
    {
        $fq_class_name = strpos($method_id, '::') !== false ? explode('::', $method_id)[0] : null;

        if ($fq_class_name && ClassLikeChecker::isUserDefined($fq_class_name)) {
            $method_params = MethodChecker::getMethodParams($method_id);

            return $method_params;
        }

        $declaring_method_id = MethodChecker::getDeclaringMethodId($method_id);

        if (FunctionChecker::inCallMap($declaring_method_id ?: $method_id)) {
            $function_param_options = FunctionChecker::getParamsFromCallMap($declaring_method_id ?: $method_id);

            if ($function_param_options === null) {
                throw new \UnexpectedValueException('Not expecting $function_param_options to be null');
            }

            return self::getMatchingParamsFromCallMapOptions($function_param_options, $args, $file_checker);
        }

        if ($method_params = MethodChecker::getMethodParams($method_id)) {
            // fall back to using reflected params anyway
            return $method_params;
        }

        throw new \InvalidArgumentException('Cannot get params for ' . $method_id);
    }

    /**
     * @param  string                           $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  FileChecker                      $file_checker
     * @return array<int, FunctionLikeParameter>
     */
    public static function getFunctionParamsFromCallMapById($method_id, array $args, FileChecker $file_checker)
    {
        $function_param_options = FunctionChecker::getParamsFromCallMap($method_id);

        if ($function_param_options === null) {
            throw new \UnexpectedValueException('Not expecting $function_param_options to be null');
        }

        return self::getMatchingParamsFromCallMapOptions($function_param_options, $args, $file_checker);
    }

     /**
     * @param  array<int, array<int, FunctionLikeParameter>>  $function_param_options
     * @param  array<int, PhpParser\Node\Arg>                 $args
     * @param  FileChecker                                    $file_checker
     * @return array<int, FunctionLikeParameter>
     */
    protected static function getMatchingParamsFromCallMapOptions(
        array $function_param_options,
        array $args,
        FileChecker $file_checker
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

                if (!isset($arg->value->inferredType)) {
                    continue;
                }

                if ($arg->value->inferredType->isMixed()) {
                    continue;
                }

                if (TypeChecker::isContainedBy($arg->value->inferredType, $param_type, $file_checker)) {
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
     * @return void
     */
    public static function clearCache()
    {
        self::$no_effects_hashes = [];
    }
}
