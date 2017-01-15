<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\Block\ForChecker;
use Psalm\Checker\Statements\Block\ForeachChecker;
use Psalm\Checker\Statements\Block\IfChecker;
use Psalm\Checker\Statements\Block\SwitchChecker;
use Psalm\Checker\Statements\Block\TryChecker;
use Psalm\Checker\Statements\Block\WhileChecker;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\Statements\Expression\CallChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\FileIncludeException;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\Issue\InvalidGlobal;
use Psalm\Issue\UnevaluatedCode;
use Psalm\Issue\UnrecognizedStatement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;

class StatementsChecker extends SourceChecker implements StatementsSource
{
    /**
     * @var StatementsSource
     */
    protected $source;

    /**
     * @var FileChecker
     */
    protected $file_checker;

    /**
     * @var array<string, CodeLocation>
     */
    private $all_vars = [];

    /**
     * @var array<string, Type\Union>
     */
    public static $stub_constants = [];

    /**
     * @var array<string, FunctionChecker>
     */
    private $function_checkers = [];

    /**
     * @param StatementsSource $source
     */
    public function __construct(StatementsSource $source)
    {
        $this->source = $source;
        $this->file_checker = $source->getFileChecker();
    }

    /**
     * Checks an array of statements for validity
     *
     * @param  array<PhpParser\Node\Stmt|PhpParser\Node\Expr>   $stmts
     * @param  Context                                          $context
     * @param  Context|null                                     $loop_context
     * @param  Context|null                                     $global_context
     *
     * @return null|false
     */
    public function analyze(
        array $stmts,
        Context $context,
        Context $loop_context = null,
        Context $global_context = null
    ) {
        $has_returned = false;

        // hoist functions to the top
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->source);
                $this->function_checkers[strtolower($stmt->name)] = $function_checker;
            }
        }

        $project_checker = $this->getFileChecker()->project_checker;

        foreach ($stmts as $stmt) {
            $plugins = Config::getInstance()->getPlugins();

            if ($plugins) {
                $code_location = new CodeLocation($this->source, $stmt);

                foreach ($plugins as $plugin) {
                    if ($plugin->checkStatement(
                        $this,
                        $stmt,
                        $context,
                        $code_location,
                        $this->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }
            }

            if ($has_returned && !($stmt instanceof PhpParser\Node\Stmt\Nop) &&
                !($stmt instanceof PhpParser\Node\Stmt\InlineHTML)
            ) {
                if ($context->collect_references) {
                    if (IssueBuffer::accepts(
                        new UnevaluatedCode(
                            'Expressions after return/throw/continue',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
                break;
            }

            /*
            if (isset($context->vars_in_scope['$failed_reconciliation']) && !$stmt instanceof PhpParser\Node\Stmt\Nop) {
                var_dump($stmt->getLine() . ' ' . $context->vars_in_scope['$failed_reconciliation']);
            }
            */

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                IfChecker::analyze($this, $stmt, $context, $loop_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                TryChecker::analyze($this, $stmt, $context, $loop_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                ForChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                ForeachChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                WhileChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $this->analyzeDo($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                $this->analyzeConstAssignment($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                foreach ($stmt->vars as $var) {
                    ExpressionChecker::analyze($this, $var, $context);

                    $var_id = ExpressionChecker::getArrayVarId(
                        $var,
                        $this->getFQCLN(),
                        $this
                    );

                    if ($var_id) {
                        $context->remove($var_id);
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                $this->analyzeReturn($project_checker, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $has_returned = true;
                $this->analyzeThrow($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                SwitchChecker::analyze($this, $stmt, $context, $loop_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                if ($loop_context === null) {
                    if (IssueBuffer::accepts(
                        new ContinueOutsideLoop(
                            'Continue call outside loop context',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                $has_returned = true;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Static_) {
                $this->analyzeStatic($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Echo_) {
                foreach ($stmt->exprs as $i => $expr) {
                    ExpressionChecker::analyze($this, $expr, $context);

                    if (isset($expr->inferredType)) {
                        if (CallChecker::checkFunctionArgumentType(
                            $this,
                            $expr->inferredType,
                            Type::getString(),
                            'echo',
                            (int)$i,
                            new CodeLocation($this->getSource(), $expr)
                        ) === false) {
                            return false;
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                if (!$project_checker->register_global_functions) {
                    $function_id = strtolower($stmt->name);
                    $function_context = new Context($context->self);
                    $function_context->collect_references = $project_checker->collect_references;
                    $this->function_checkers[$function_id]->analyze($function_context, $context);

                    $config = Config::getInstance();

                    if ($config->reportIssueInFile('InvalidReturnType', $this->getFilePath())) {
                        /** @var string */
                        $method_id = $this->function_checkers[$function_id]->getMethodId();

                        $function_storage = FunctionChecker::getStorage(
                            $this,
                            $method_id
                        );

                        $return_type = $function_storage->return_type;
                        $return_type_location = $function_storage->return_type_location;

                        $this->function_checkers[$function_id]->verifyReturnType(
                            $project_checker,
                            $return_type,
                            $this->getFQCLN(),
                            $return_type_location
                        );
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Expr) {
                ExpressionChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\InlineHTML) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Global_) {
                if (!$context->collect_initializations && !$global_context) {
                    if (IssueBuffer::accepts(
                        new InvalidGlobal(
                            'Cannot use global scope here',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                foreach ($stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name)) {
                            $var_id = '$' . $var->name;

                            $context->vars_in_scope[$var_id] =
                                $global_context && $global_context->hasVariable($var_id)
                                    ? clone $global_context->vars_in_scope[$var_id]
                                    : Type::getMixed();

                            $context->vars_possibly_in_scope[$var_id] = true;
                        } else {
                            ExpressionChecker::analyze($this, $var, $context);
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        ExpressionChecker::analyze($this, $prop->default, $context);

                        if (isset($prop->default->inferredType)) {
                            if (!$stmt->isStatic()) {
                                if (AssignmentChecker::analyzePropertyAssignment(
                                    $this,
                                    $prop,
                                    $prop->name,
                                    $prop->default,
                                    $prop->default->inferredType,
                                    $context
                                ) === false) {
                                    // fall through
                                }
                            }
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $const_visibility = \ReflectionProperty::IS_PUBLIC;

                foreach ($stmt->consts as $const) {
                    ExpressionChecker::analyze($this, $const->value, $context);

                    if (isset($const->value->inferredType) && !$const->value->inferredType->isMixed()) {
                        ClassLikeChecker::setConstantType(
                            $project_checker,
                            (string)$this->getFQCLN(),
                            $const->name,
                            $const->value->inferredType,
                            $const_visibility
                        );
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                $class_checker = (new ClassChecker($stmt, $this->source, $stmt->name));
                $class_checker->analyze(null, $global_context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                if ((string)$stmt->getDocComment()) {
                    $var_comment = CommentChecker::getTypeFromComment(
                        (string)$stmt->getDocComment(),
                        $context,
                        $this->getSource(),
                        $this->getSource()->getAliases()
                    );

                    if ($var_comment && $var_comment->var_id) {
                        $comment_type = ExpressionChecker::fleshOutType(
                            $project_checker,
                            Type::parseString($var_comment->type),
                            $context->self
                        );

                        $context->vars_in_scope[$var_comment->var_id] = $comment_type;
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Goto_) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Label) {
                // do nothing
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Declare_) {
                // do nothing
            } else {
                if (IssueBuffer::accepts(
                    new UnrecognizedStatement(
                        'Psalm does not understand ' . get_class($stmt),
                        new CodeLocation($this->source, $stmt)
                    ),
                    $this->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * Checks an array of statements in a loop
     *
     * @param  array<PhpParser\Node\Stmt|PhpParser\Node\Expr>   $stmts
     * @param  array<int, string>                               $asserted_vars
     * @param  Context                                          $loop_context
     * @param  Context                                          $outer_context
     *
     * @return void
     */
    public function analyzeLoop(
        array $stmts,
        array $asserted_vars,
        Context $loop_context,
        Context $outer_context
    ) {
        $traverser = new PhpParser\NodeTraverser;

        $assignment_mapper = new \Psalm\Visitor\AssignmentMapVisitor($loop_context->self);
        $traverser->addVisitor($assignment_mapper);

        $traverser->traverse($stmts);

        $assignment_map = $assignment_mapper->getAssignmentMap();

        $assignment_depth = 0;

        if ($assignment_map) {
            $first_var_id = array_keys($assignment_map)[0];

            $assignment_depth = self::getAssignmentMapDepth($first_var_id, $assignment_map);
        }

        if ($assignment_depth === 0) {
            $this->analyze($stmts, $loop_context, $outer_context);
        } else {
            // record all the vars that existed before we did the first pass through the loop
            $pre_loop_context = clone $loop_context;
            $pre_outer_context = clone $outer_context;

            IssueBuffer::startRecording();
            $this->analyze($stmts, $loop_context, $outer_context);
            $recorded_issues = IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();

            for ($i = 0; $i < $assignment_depth; ++$i) {
                $vars_to_remove = [];

                $has_changes = false;

                foreach ($loop_context->vars_in_scope as $var_id => $type) {
                    if (in_array($var_id, $asserted_vars, true)) {
                        // set the vars to whatever the while/foreach loop expects them to be
                        if ((string)$type !== (string)$pre_loop_context->vars_in_scope[$var_id]) {
                            $loop_context->vars_in_scope[$var_id] = $pre_loop_context->vars_in_scope[$var_id];
                            $has_changes = true;
                        }
                    } elseif (isset($pre_outer_context->vars_in_scope[$var_id])) {
                        $pre_outer = (string)$pre_outer_context->vars_in_scope[$var_id];

                        if ((string)$type !== $pre_outer ||
                            (string)$outer_context->vars_in_scope[$var_id] !== $pre_outer
                        ) {
                            $has_changes = true;

                            // widen the foreach context type with the initial context type
                            $loop_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                                $loop_context->vars_in_scope[$var_id],
                                $outer_context->vars_in_scope[$var_id]
                            );

                            // if there's a change, invalidate related clauses
                            $pre_loop_context->removeVarFromConflictingClauses($var_id);
                        }
                    } else {
                        $vars_to_remove[] = $var_id;
                    }
                }

                foreach ($asserted_vars as $var_id) {
                    if (!isset($loop_context->vars_in_scope[$var_id])) {
                        $loop_context->vars_in_scope[$var_id] = $pre_loop_context->vars_in_scope[$var_id];
                    }
                }

                // if there are no changes to the types, no need to re-examine
                if (!$has_changes) {
                    break;
                }

                // remove vars that were defined in the foreach
                foreach ($vars_to_remove as $var_id) {
                    unset($loop_context->vars_in_scope[$var_id]);
                }

                $loop_context->clauses = $pre_loop_context->clauses;

                IssueBuffer::startRecording();
                $this->analyze($stmts, $loop_context, $outer_context);
                $recorded_issues = IssueBuffer::clearRecordingLevel();
                IssueBuffer::stopRecording();
            }

            if ($recorded_issues) {
                foreach ($recorded_issues as $recorded_issue) {
                    // if we're not in any loops then this will just result in the issue being emitted
                    IssueBuffer::bubbleUp($recorded_issue);
                }
            }
        }
    }

    /**
     * @param  string                               $first_var_id
     * @param  array<string, array<string, bool>>   $assignment_map
     *
     * @return int
     */
    private static function getAssignmentMapDepth($first_var_id, array $assignment_map)
    {
        $max_depth = 0;

        $assignment_var_ids = $assignment_map[$first_var_id];
        unset($assignment_map[$first_var_id]);

        foreach ($assignment_var_ids as $assignment_var_id => $_) {
            $depth = 1;

            if (isset($assignment_map[$assignment_var_id])) {
                $depth = 1 + self::getAssignmentMapDepth($assignment_var_id, $assignment_map);
            }

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }

        return $max_depth;
    }

    /**
     * @param   PhpParser\Node\Stmt\Static_ $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    private function analyzeStatic(PhpParser\Node\Stmt\Static_ $stmt, Context $context)
    {
        foreach ($stmt->vars as $var) {
            if ($var->default) {
                if (ExpressionChecker::analyze($this, $var->default, $context) === false) {
                    return false;
                }
            }

            if ($context->check_variables) {
                $context->vars_in_scope['$' . $var->name] = Type::getMixed();
                $context->vars_possibly_in_scope['$' . $var->name] = true;
                $this->registerVariable('$' . $var->name, new CodeLocation($this, $stmt));
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr $stmt
     * @param   array<string, Type\Union> $existing_class_constants
     *
     * @return  Type\Union|null
     */
    public static function getSimpleType(
        PhpParser\Node\Expr $stmt,
        StatementsSource $statements_source = null,
        array $existing_class_constants = []
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
                return Type::getString();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalAnd
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Greater
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\GreaterOrEqual
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Smaller
                || $stmt instanceof PhpParser\Node\Expr\BinaryOp\SmallerOrEqual
            ) {
                return Type::getBool();
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Coalesce) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Spaceship) {
                return Type::getInt();
            }

            $stmt->left->inferredType = self::getSimpleType(
                $stmt->left,
                $statements_source,
                $existing_class_constants
            );
            $stmt->right->inferredType = self::getSimpleType(
                $stmt->right,
                $statements_source,
                $existing_class_constants
            );

            if (!$stmt->left->inferredType || !$stmt->right->inferredType) {
                return null;
            }

            if (!$statements_source) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
            ) {
                ExpressionChecker::analyzeNonDivArithmenticOp(
                    $statements_source,
                    $stmt->left,
                    $stmt->right,
                    $stmt,
                    $result_type
                );

                if ($result_type) {
                    return $result_type;
                }

                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Div
                && ($stmt->left->inferredType->hasInt() || $stmt->left->inferredType->hasFloat())
                && ($stmt->right->inferredType->hasInt() || $stmt->right->inferredType->hasFloat())
            ) {
                return Type::combineUnionTypes(Type::getFloat(), Type::getInt());
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            if (strtolower($stmt->name->parts[0]) === 'false') {
                return Type::getFalse();
            } elseif (strtolower($stmt->name->parts[0]) === 'true') {
                return Type::getBool();
            } elseif (strtolower($stmt->name->parts[0]) === 'null') {
                return Type::getNull();
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if ($stmt->class instanceof PhpParser\Node\Name
                && $stmt->class->parts !== ['static']
                && is_string($stmt->name)
                && isset($existing_class_constants[$stmt->name])
            ) {
                return $existing_class_constants[$stmt->name];
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            return Type::getFloat();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (count($stmt->items) === 0) {
                return Type::getEmptyArray();
            }

            return Type::getArray();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            return Type::getInt();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            return Type::getFloat();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            return Type::getBool();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            return Type::getString();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            return Type::getObject();
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            return Type::getArray();
        }

        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return self::getSimpleType($stmt->expr, $statements_source, $existing_class_constants);
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Do_ $stmt
     * @param   Context                 $context
     *
     * @return  false|null
     */
    private function analyzeDo(PhpParser\Node\Stmt\Do_ $stmt, Context $context)
    {
        $do_context = clone $context;

        $this->analyzeLoop($stmt->stmts, [], $do_context, $context);

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($do_context->hasVariable($var)) {
                if ($do_context->vars_in_scope[$var]->isMixed()) {
                    $context->vars_in_scope[$var] = $do_context->vars_in_scope[$var];
                }

                if ((string)$do_context->vars_in_scope[$var] !== (string)$type) {
                    $context->vars_in_scope[$var] = Type::combineUnionTypes($do_context->vars_in_scope[$var], $type);
                }
            }
        }

        foreach ($do_context->vars_in_scope as $var_id => $type) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = $type;
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $do_context->vars_possibly_in_scope
        );

        if ($context->collect_references) {
            $context->referenced_vars = array_merge(
                $context->referenced_vars,
                $do_context->referenced_vars
            );
        }

        return ExpressionChecker::analyze($this, $stmt->cond, $context);
    }

    /**
     * @param   PhpParser\Node\Stmt\Const_  $stmt
     * @param   Context                     $context
     *
     * @return  void
     */
    private function analyzeConstAssignment(PhpParser\Node\Stmt\Const_ $stmt, Context $context)
    {
        foreach ($stmt->consts as $const) {
            ExpressionChecker::analyze($this, $const->value, $context);

            $this->setConstType(
                $const->name,
                isset($const->value->inferredType) ? $const->value->inferredType : Type::getMixed(),
                $context
            );
        }
    }

    /**
     * @param   string  $const_name
     * @param   bool    $is_fully_qualified
     * @param   Context $context
     *
     * @return  Type\Union|null
     */
    public function getConstType(
        StatementsChecker $statements_checker,
        $const_name,
        $is_fully_qualified,
        Context $context
    ) {
        $fq_const_name = null;

        $aliased_constants = $this->getAliases()->constants;

        if (isset($aliased_constants[$const_name])) {
            $fq_const_name = $aliased_constants[$const_name];
        } elseif ($is_fully_qualified) {
            $fq_const_name = $const_name;
        } elseif (strpos($const_name, '\\')) {
            $fq_const_name = ClassLikeChecker::getFQCLNFromString($const_name, $this->getAliases());
        }

        if ($fq_const_name) {
            $const_name_parts = explode('\\', $fq_const_name);
            $const_name = array_pop($const_name_parts);
            $namespace_name = implode('\\', $const_name_parts);
            $namespace_constants = NamespaceChecker::getConstantsForNamespace(
                $namespace_name,
                \ReflectionProperty::IS_PUBLIC
            );

            if (isset($namespace_constants[$const_name])) {
                return $namespace_constants[$const_name];
            }
        }

        if ($context->hasVariable($const_name)) {
            return $context->vars_in_scope[$const_name];
        }

        $file_path = $statements_checker->getFilePath();

        $file_storage_provider = $statements_checker->getFileChecker()->project_checker->file_storage_provider;

        $file_storage = $file_storage_provider->get($file_path);

        if (isset($file_storage->declaring_constants[$const_name])) {
            $constant_file_path = $file_storage->declaring_constants[$const_name];

            return $file_storage_provider->get($constant_file_path)->constants[$const_name];
        }

        $predefined_constants = Config::getInstance()->getPredefinedConstants();

        if (isset($predefined_constants[$fq_const_name ?: $const_name])) {
            return ClassLikeChecker::getTypeFromValue($predefined_constants[$fq_const_name ?: $const_name]);
        }

        if (isset(self::$stub_constants[$fq_const_name ?: $const_name])) {
            return self::$stub_constants[$fq_const_name ?: $const_name];
        }

        return null;
    }

    /**
     * @param   string      $const_name
     * @param   Type\Union  $const_type
     * @param   Context     $context
     *
     * @return  void
     */
    public function setConstType($const_name, Type\Union $const_type, Context $context)
    {
        $context->vars_in_scope[$const_name] = $const_type;
        $context->constants[$const_name] = $const_type;

        if ($this->source instanceof NamespaceChecker) {
            $this->source->setConstType($const_name, $const_type);
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\Return_ $stmt
     * @param  Context                     $context
     *
     * @return false|null
     */
    private function analyzeReturn(
        ProjectChecker $project_checker,
        PhpParser\Node\Stmt\Return_ $stmt,
        Context $context
    ) {
        $doc_comment_text = (string)$stmt->getDocComment();

        $var_comment = null;

        if ($doc_comment_text) {
            $var_comment = CommentChecker::getTypeFromComment(
                $doc_comment_text,
                $context,
                $this->source,
                $this->source->getAliases()
            );

            if ($var_comment && $var_comment->var_id) {
                $comment_type = ExpressionChecker::fleshOutType(
                    $project_checker,
                    Type::parseString($var_comment->type),
                    $context->self
                );

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->expr) {
            if (ExpressionChecker::analyze($this, $stmt->expr, $context) === false) {
                return false;
            }

            if ($var_comment && !$var_comment->var_id) {
                $stmt->inferredType = Type::parseString($var_comment->type);
            } elseif (isset($stmt->expr->inferredType)) {
                $stmt->inferredType = $stmt->expr->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $stmt->inferredType = Type::getVoid();
        }

        if ($this->source instanceof FunctionLikeChecker) {
            $this->source->addReturnTypes($stmt->expr ? (string) $stmt->inferredType : '', $context);
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Throw_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    private function analyzeThrow(PhpParser\Node\Stmt\Throw_ $stmt, Context $context)
    {
        return ExpressionChecker::analyze($this, $stmt->expr, $context);
    }

    /**
     * @param  string       $var_name
     *
     * @return bool
     */
    public function hasVariable($var_name)
    {
        return isset($this->all_vars[$var_name]);
    }

    /**
     * @param  string       $var_name
     * @param  CodeLocation $location
     *
     * @return void
     */
    public function registerVariable($var_name, CodeLocation $location)
    {
        $this->all_vars[$var_name] = $location;
    }

    /**
     * @param  PhpParser\Node\Expr\Include_ $stmt
     * @param  Context                      $context
     *
     * @return false|null
     */
    public function analyzeInclude(PhpParser\Node\Expr\Include_ $stmt, Context $context)
    {
        $config = Config::getInstance();

        if (!$config->allow_includes) {
            throw new FileIncludeException(
                'File includes are not allowed per your Psalm config - check the allowFileIncludes flag.'
            );
        }

        if (ExpressionChecker::analyze($this, $stmt->expr, $context) === false) {
            return false;
        }

        $path_to_file = null;

        if ($stmt->expr instanceof PhpParser\Node\Scalar\String_) {
            $path_to_file = $stmt->expr->value;

            // attempts to resolve using get_include_path dirs
            $include_path = self::resolveIncludePath($path_to_file, dirname($this->getCheckedFileName()));
            $path_to_file = $include_path ? $include_path : $path_to_file;

            if ($path_to_file[0] !== DIRECTORY_SEPARATOR) {
                $path_to_file = getcwd() . DIRECTORY_SEPARATOR . $path_to_file;
            }
        } else {
            $path_to_file = self::getPathTo($stmt->expr, $this->getCheckedFileName());
        }

        if ($path_to_file) {
            $reduce_pattern = '/\/[^\/]+\/\.\.\//';

            while (preg_match($reduce_pattern, $path_to_file)) {
                $path_to_file = preg_replace($reduce_pattern, DIRECTORY_SEPARATOR, $path_to_file);
            }

            // if the file is already included, we can't check much more
            if (in_array($path_to_file, get_included_files(), true)) {
                return null;
            }

            if ($this->getFilePath() === $path_to_file) {
                return null;
            }

            $current_file_checker = $this->getFileChecker();

            if ($current_file_checker->project_checker->fileExists($path_to_file)) {
                if (is_subclass_of($current_file_checker, 'Psalm\\Checker\\FileChecker')) {
                    $include_file_checker = new FileChecker(
                        $path_to_file,
                        $current_file_checker->project_checker,
                        false
                    );
                    $this->analyze($include_file_checker->getStatements(), $context);
                }

                return null;
            }
        }

        $context->check_classes = false;
        $context->check_variables = false;
        $context->check_functions = false;

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr $stmt
     * @param  string              $file_name
     *
     * @return string|null
     * @psalm-suppress MixedAssignment
     */
    public static function getPathTo(PhpParser\Node\Expr $stmt, $file_name)
    {
        if ($file_name[0] !== DIRECTORY_SEPARATOR) {
            $file_name = getcwd() . DIRECTORY_SEPARATOR . $file_name;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return $stmt->value;
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            $left_string = self::getPathTo($stmt->left, $file_name);
            $right_string = self::getPathTo($stmt->right, $file_name);

            if ($left_string && $right_string) {
                return $left_string . $right_string;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall &&
            $stmt->name instanceof PhpParser\Node\Name &&
            $stmt->name->parts === ['dirname']
        ) {
            if ($stmt->args) {
                $evaled_path = self::getPathTo($stmt->args[0]->value, $file_name);

                if (!$evaled_path) {
                    return null;
                }

                return dirname($evaled_path);
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch && $stmt->name instanceof PhpParser\Node\Name) {
            $const_name = implode('', $stmt->name->parts);

            if (defined($const_name)) {
                $constant_value = constant($const_name);

                if (is_string($constant_value)) {
                    return $constant_value;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\Dir) {
            return dirname($file_name);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst\File) {
            return $file_name;
        }

        return null;
    }

    /**
     * @param   string  $file_name
     * @param   string  $current_directory
     *
     * @return  string|null
     */
    public static function resolveIncludePath($file_name, $current_directory)
    {
        if (!$current_directory) {
            return $file_name;
        }

        $paths = PATH_SEPARATOR == ':'
            ? preg_split('#(?<!phar):#', get_include_path())
            : explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $prefix) {
            $ds = substr($prefix, -1) == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;

            if ($prefix === '.') {
                $prefix = $current_directory;
            }

            $file = $prefix . $ds . $file_name;

            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * The first appearance of the variable in this set of statements being evaluated
     *
     * @param  string  $var_name
     *
     * @return CodeLocation|null
     */
    public function getFirstAppearance($var_name)
    {
        return isset($this->all_vars[$var_name]) ? $this->all_vars[$var_name] : null;
    }

    public function getFileChecker()
    {
        return $this->file_checker;
    }

    /**
     * @return array<string, FunctionChecker>
     */
    public function getFunctionCheckers()
    {
        return $this->function_checkers;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$stub_constants = [];

        ExpressionChecker::clearCache();
    }
}
