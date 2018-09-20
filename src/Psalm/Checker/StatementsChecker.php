<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\Statements\Block\DoChecker;
use Psalm\Checker\Statements\Block\ForChecker;
use Psalm\Checker\Statements\Block\ForeachChecker;
use Psalm\Checker\Statements\Block\IfChecker;
use Psalm\Checker\Statements\Block\SwitchChecker;
use Psalm\Checker\Statements\Block\TryChecker;
use Psalm\Checker\Statements\Block\WhileChecker;
use Psalm\Checker\Statements\Expression\Assignment\PropertyAssignmentChecker;
use Psalm\Checker\Statements\Expression\BinaryOpChecker;
use Psalm\Checker\Statements\Expression\CallChecker;
use Psalm\Checker\Statements\Expression\Fetch\ConstFetchChecker;
use Psalm\Checker\Statements\Expression\Fetch\VariableFetchChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\Statements\ReturnChecker;
use Psalm\Checker\Statements\ThrowChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\FileManipulation\FileManipulation;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ForbiddenEcho;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidGlobal;
use Psalm\Issue\UnevaluatedCode;
use Psalm\Issue\UnrecognizedStatement;
use Psalm\Issue\UnusedVariable;
use Psalm\IssueBuffer;
use Psalm\Scope\LoopScope;
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
     * @var array<string, int>
     */
    private $var_branch_points = [];

    /**
     * Possibly undefined variables should be initialised if we're altering code
     *
     * @var array<string, int>|null
     */
    private $vars_to_initialize;

    /**
     * @var array<string, FunctionChecker>
     */
    private $function_checkers = [];

    /**
     * @var array<string, array{0: string, 1: CodeLocation}>
     */
    private $unused_var_locations = [];

    /**
     * @var array<string, bool>
     */
    private $used_var_locations = [];

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
     * @param  array<PhpParser\Node\Stmt>   $stmts
     * @param  Context                                          $context
     * @param  Context|null                                     $global_context
     * @param  bool                                             $root_scope
     *
     * @return null|false
     */
    public function analyze(
        array $stmts,
        Context $context,
        Context $global_context = null,
        $root_scope = false
    ) {
        $has_returned = false;

        // hoist functions to the top
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checker = new FunctionChecker($stmt, $this->source);
                $this->function_checkers[strtolower($stmt->name->name)] = $function_checker;
            }
        }

        $project_checker = $this->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        if ($codebase->config->hoist_constants) {
            foreach ($stmts as $stmt) {
                if ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                    foreach ($stmt->consts as $const) {
                        $this->setConstType(
                            $const->name->name,
                            self::getSimpleType($codebase, $const->value, $this->getAliases(), $this)
                                ?: Type::getMixed(),
                            $context
                        );
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression
                    && $stmt->expr instanceof PhpParser\Node\Expr\FuncCall
                    && $stmt->expr->name instanceof PhpParser\Node\Name
                    && $stmt->expr->name->parts === ['define']
                    && isset($stmt->expr->args[1])
                    && $stmt->expr->args[0]->value instanceof PhpParser\Node\Scalar\String_
                ) {
                    $const_name = $stmt->expr->args[0]->value->value;

                    $this->setConstType(
                        $const_name,
                        self::getSimpleType($codebase, $stmt->expr->args[1]->value, $this->getAliases(), $this)
                            ?: Type::getMixed(),
                        $context
                    );
                }
            }
        }

        $original_context = null;

        if ($context->loop_scope) {
            $original_context = clone $context->loop_scope->loop_parent_context;
        }

        $plugin_classes = $codebase->config->after_statement_checks;

        foreach ($stmts as $stmt) {
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

            if ($project_checker->debug_lines) {
                echo $this->getFilePath() . ':' . $stmt->getLine() . "\n";
            }

            /*
            if (isset($context->vars_in_scope['$array']) && !$stmt instanceof PhpParser\Node\Stmt\Nop) {
                var_dump($stmt->getLine(), $context->vars_in_scope['$array']);
            }
            */

            $new_issues = null;

            if ($docblock = $stmt->getDocComment()) {
                $comments = CommentChecker::parseDocComment((string)$docblock);
                if (isset($comments['specials']['psalm-suppress'])) {
                    $suppressed = array_filter(
                        array_map(
                            /**
                             * @param string $line
                             *
                             * @return string
                             */
                            function ($line) {
                                return explode(' ', trim($line))[0];
                            },
                            $comments['specials']['psalm-suppress']
                        )
                    );

                    if ($suppressed) {
                        $new_issues = array_diff($suppressed, $this->source->getSuppressedIssues());
                        /** @psalm-suppress MixedTypeCoercion */
                        $this->addSuppressedIssues($new_issues);
                    }
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if (IfChecker::analyze($this, $stmt, $context) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                if (TryChecker::analyze($this, $stmt, $context) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                if (ForChecker::analyze($this, $stmt, $context) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                if (ForeachChecker::analyze($this, $stmt, $context) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                if (WhileChecker::analyze($this, $stmt, $context) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                DoChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Const_) {
                $this->analyzeConstAssignment($stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Unset_) {
                $context->inside_unset = true;

                foreach ($stmt->vars as $var) {
                    ExpressionChecker::analyze($this, $var, $context);

                    $var_id = ExpressionChecker::getArrayVarId(
                        $var,
                        $this->getFQCLN(),
                        $this
                    );

                    if ($var_id) {
                        $context->remove($var_id);

                        if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch
                            && $var->dim
                            && ($var->dim instanceof PhpParser\Node\Scalar\String_
                                || $var->dim instanceof PhpParser\Node\Scalar\LNumber
                            )
                        ) {
                            $root_var_id = ExpressionChecker::getArrayVarId(
                                $var->var,
                                $this->getFQCLN(),
                                $this
                            );

                            if ($root_var_id && isset($context->vars_in_scope[$root_var_id])) {
                                $root_type = clone $context->vars_in_scope[$root_var_id];

                                foreach ($root_type->getTypes() as $atomic_root_type) {
                                    if ($atomic_root_type instanceof Type\Atomic\ObjectLike) {
                                        if (isset($atomic_root_type->properties[$var->dim->value])) {
                                            unset($atomic_root_type->properties[$var->dim->value]);
                                        }

                                        if (!$atomic_root_type->properties) {
                                            $root_type->addType(
                                                new Type\Atomic\TArray([
                                                    new Type\Union([new Type\Atomic\TEmpty]),
                                                    new Type\Union([new Type\Atomic\TEmpty]),
                                                ])
                                            );
                                        }
                                    }
                                }

                                $context->vars_in_scope[$root_var_id] = $root_type;
                            }
                        }
                    }
                }

                $context->inside_unset = false;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $has_returned = true;
                ReturnChecker::analyze($this, $project_checker, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                $has_returned = true;
                ThrowChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                SwitchChecker::analyze($this, $stmt, $context);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                $loop_scope = $context->loop_scope;
                if ($loop_scope && $original_context) {
                    $loop_scope->final_actions[] = ScopeChecker::ACTION_BREAK;

                    $redefined_vars = $context->getRedefinedVars($loop_scope->loop_parent_context->vars_in_scope);

                    if ($loop_scope->possibly_redefined_loop_parent_vars === null) {
                        $loop_scope->possibly_redefined_loop_parent_vars = $redefined_vars;
                    } else {
                        foreach ($redefined_vars as $var => $type) {
                            if ($type->isMixed()) {
                                $loop_scope->possibly_redefined_loop_parent_vars[$var] = $type;
                            } elseif (isset($loop_scope->possibly_redefined_loop_parent_vars[$var])) {
                                $loop_scope->possibly_redefined_loop_parent_vars[$var] = Type::combineUnionTypes(
                                    $type,
                                    $loop_scope->possibly_redefined_loop_parent_vars[$var]
                                );
                            } else {
                                $loop_scope->possibly_redefined_loop_parent_vars[$var] = $type;
                            }
                        }
                    }

                    if ($context->collect_references && (!$context->switch_scope || $stmt->num)) {
                        foreach ($context->unreferenced_vars as $var_id => $locations) {
                            if (isset($loop_scope->unreferenced_vars[$var_id])) {
                                $loop_scope->unreferenced_vars[$var_id] += $locations;
                            } else {
                                $loop_scope->unreferenced_vars[$var_id] = $locations;
                            }
                        }
                    }
                }

                $switch_scope = $context->switch_scope;
                if ($switch_scope && $context->collect_references) {
                    foreach ($context->unreferenced_vars as $var_id => $locations) {
                        if (isset($switch_scope->unreferenced_vars[$var_id])) {
                            $switch_scope->unreferenced_vars[$var_id] += $locations;
                        } else {
                            $switch_scope->unreferenced_vars[$var_id] = $locations;
                        }
                    }
                }

                $has_returned = true;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Continue_) {
                $loop_scope = $context->loop_scope;
                if ($loop_scope === null) {
                    if (!$context->inside_case) {
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
                } elseif ($original_context) {
                    $loop_scope->final_actions[] = ScopeChecker::ACTION_CONTINUE;

                    $redefined_vars = $context->getRedefinedVars($original_context->vars_in_scope);

                    if ($loop_scope->redefined_loop_vars === null) {
                        $loop_scope->redefined_loop_vars = $redefined_vars;
                    } else {
                        foreach ($loop_scope->redefined_loop_vars as $redefined_var => $type) {
                            if (!isset($redefined_vars[$redefined_var])) {
                                unset($loop_scope->redefined_loop_vars[$redefined_var]);
                            } else {
                                $loop_scope->redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                                    $redefined_vars[$redefined_var],
                                    $type
                                );
                            }
                        }
                    }

                    foreach ($redefined_vars as $var => $type) {
                        if ($type->isMixed()) {
                            $loop_scope->possibly_redefined_loop_vars[$var] = $type;
                        } elseif (isset($loop_scope->possibly_redefined_loop_vars[$var])) {
                            $loop_scope->possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                                $type,
                                $loop_scope->possibly_redefined_loop_vars[$var]
                            );
                        } else {
                            $loop_scope->possibly_redefined_loop_vars[$var] = $type;
                        }
                    }

                    if ($context->collect_references && (!$context->switch_scope || $stmt->num)) {
                        foreach ($context->unreferenced_vars as $var_id => $locations) {
                            if (isset($loop_scope->possibly_unreferenced_vars[$var_id])) {
                                $loop_scope->possibly_unreferenced_vars[$var_id] += $locations;
                            } else {
                                $loop_scope->possibly_unreferenced_vars[$var_id] = $locations;
                            }
                        }
                    }
                }

                $switch_scope = $context->switch_scope;
                if ($switch_scope && $context->collect_references) {
                    foreach ($context->unreferenced_vars as $var_id => $locations) {
                        if (isset($switch_scope->unreferenced_vars[$var_id])) {
                            $switch_scope->unreferenced_vars[$var_id] += $locations;
                        } else {
                            $switch_scope->unreferenced_vars[$var_id] = $locations;
                        }
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
                            new CodeLocation($this->getSource(), $expr),
                            $expr,
                            $context
                        ) === false) {
                            return false;
                        }
                    }
                }

                if ($codebase->config->forbid_echo) {
                    if (IssueBuffer::accepts(
                        new ForbiddenEcho(
                            'Use of echo',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } elseif (isset($codebase->config->forbidden_functions['echo'])) {
                    if (IssueBuffer::accepts(
                        new ForbiddenCode(
                            'Use of echo',
                            new CodeLocation($this->source, $stmt)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                foreach ($stmt->stmts as $function_stmt) {
                    if ($function_stmt instanceof PhpParser\Node\Stmt\Global_) {
                        foreach ($function_stmt->vars as $var) {
                            if ($var instanceof PhpParser\Node\Expr\Variable) {
                                if (is_string($var->name)) {
                                    $var_id = '$' . $var->name;

                                    // registers variable in global context
                                    $context->hasVariable($var_id, $this);
                                }
                            }
                        }
                    } elseif (!$function_stmt instanceof PhpParser\Node\Stmt\Nop) {
                        break;
                    }
                }

                if (!$project_checker->codebase->register_stub_files
                    && !$project_checker->codebase->register_autoload_files
                ) {
                    $function_id = strtolower($stmt->name->name);
                    $function_context = new Context($context->self);
                    $config = Config::getInstance();
                    $function_context->collect_references = $project_checker->codebase->collect_references;
                    $function_context->collect_exceptions = $config->check_for_throws_docblock;
                    $this->function_checkers[$function_id]->analyze($function_context, $context);

                    if ($config->reportIssueInFile('InvalidReturnType', $this->getFilePath())) {
                        $method_id = $this->function_checkers[$function_id]->getMethodId();

                        $function_storage = $codebase->functions->getStorage(
                            $this,
                            $method_id
                        );

                        $return_type = $function_storage->return_type;
                        $return_type_location = $function_storage->return_type_location;

                        $this->function_checkers[$function_id]->verifyReturnType(
                            $this,
                            $return_type,
                            $this->getFQCLN(),
                            $return_type_location
                        );
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Expression) {
                if (ExpressionChecker::analyze($this, $stmt->expr, $context, false, $global_context) === false) {
                    return false;
                }
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

                $source = $this->getSource();
                $function_storage = $source instanceof FunctionLikeChecker
                    ? $source->getFunctionLikeStorage($this)
                    : null;

                foreach ($stmt->vars as $var) {
                    if ($var instanceof PhpParser\Node\Expr\Variable) {
                        if (is_string($var->name)) {
                            $var_id = '$' . $var->name;

                            if ($var->name === 'argv' || $var->name === 'argc') {
                                if ($var->name === 'argv') {
                                    $context->vars_in_scope[$var_id] = new Type\Union([
                                        new Type\Atomic\TArray([
                                            Type::getInt(),
                                            Type::getString(),
                                        ]),
                                    ]);
                                } else {
                                    $context->vars_in_scope[$var_id] = Type::getInt();
                                }
                            } elseif (isset($function_storage->global_types[$var_id])) {
                                $context->vars_in_scope[$var_id] = clone $function_storage->global_types[$var_id];
                                $context->vars_possibly_in_scope[$var_id] = true;
                            } else {
                                $context->vars_in_scope[$var_id] =
                                    $global_context && $global_context->hasVariable($var_id, $this)
                                        ? clone $global_context->vars_in_scope[$var_id]
                                        : Type::getMixed();

                                $context->vars_possibly_in_scope[$var_id] = true;
                            }
                        }
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        ExpressionChecker::analyze($this, $prop->default, $context);

                        if (isset($prop->default->inferredType)) {
                            if (!$stmt->isStatic()) {
                                if (PropertyAssignmentChecker::analyzeInstance(
                                    $this,
                                    $prop,
                                    $prop->name->name,
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

                if ($stmt->isProtected()) {
                    $const_visibility = \ReflectionProperty::IS_PROTECTED;
                }

                if ($stmt->isPrivate()) {
                    $const_visibility = \ReflectionProperty::IS_PRIVATE;
                }

                foreach ($stmt->consts as $const) {
                    ExpressionChecker::analyze($this, $const->value, $context);

                    if (isset($const->value->inferredType) && !$const->value->inferredType->isMixed()) {
                        $codebase->classlikes->setConstantType(
                            (string)$this->getFQCLN(),
                            $const->name->name,
                            $const->value->inferredType,
                            $const_visibility
                        );
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                try {
                    $class_checker = new ClassChecker($stmt, $this->source, $stmt->name ? $stmt->name->name : null);
                    $class_checker->analyze(null, $global_context);
                } catch (\InvalidArgumentException $e) {
                    // disregard this exception, we'll likely see it elsewhere in the form
                    // of an issue
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                if ((string)$stmt->getDocComment()) {
                    $var_comments = [];

                    try {
                        $var_comments = CommentChecker::getTypeFromComment(
                            (string)$stmt->getDocComment(),
                            $this->getSource(),
                            $this->getSource()->getAliases()
                        );
                    } catch (DocblockParseException $e) {
                        if (IssueBuffer::accepts(
                            new InvalidDocblock(
                                (string)$e->getMessage(),
                                new CodeLocation($this->getSource(), $stmt, null, true)
                            )
                        )) {
                            // fall through
                        }
                    }

                    foreach ($var_comments as $var_comment) {
                        if (!$var_comment->var_id) {
                            continue;
                        }

                        $comment_type = ExpressionChecker::fleshOutType(
                            $project_checker,
                            $var_comment->type,
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
                foreach ($stmt->declares as $declaration) {
                    if ((string) $declaration->key === 'strict_types'
                        && $declaration->value instanceof PhpParser\Node\Scalar\LNumber
                        && $declaration->value->value === 1
                    ) {
                        $context->strict_types = true;
                    }
                }
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

            if ($context->loop_scope
                && $context->loop_scope->final_actions
                && !in_array(ScopeChecker::ACTION_NONE, $context->loop_scope->final_actions, true)
            ) {
                //$has_returned = true;
            }

            if ($plugin_classes) {
                $file_manipulations = [];
                $code_location = new CodeLocation($this->source, $stmt);

                foreach ($plugin_classes as $plugin_fq_class_name) {
                    if ($plugin_fq_class_name::afterStatementCheck(
                        $this,
                        $stmt,
                        $context,
                        $code_location,
                        $this->getSuppressedIssues(),
                        $file_manipulations
                    ) === false) {
                        return false;
                    }
                }

                if ($file_manipulations) {
                    /** @psalm-suppress MixedTypeCoercion */
                    FileManipulationBuffer::add($this->getFilePath(), $file_manipulations);
                }
            }

            if ($new_issues) {
                /** @psalm-suppress MixedTypeCoercion */
                $this->removeSuppressedIssues($new_issues);
            }
        }

        if ($root_scope
            && $context->collect_references
            && !$context->collect_initializations
            && $project_checker->codebase->find_unused_code
            && $context->check_variables
        ) {
            $this->checkUnreferencedVars();
        }

        if ($project_checker->alter_code && $root_scope && $this->vars_to_initialize) {
            $file_contents = $project_checker->codebase->getFileContents($this->getFilePath());

            foreach ($this->vars_to_initialize as $var_id => $branch_point) {
                $newline_pos = (int)strrpos($file_contents, "\n", $branch_point - strlen($file_contents)) + 1;
                $indentation = substr($file_contents, $newline_pos, $branch_point - $newline_pos);
                FileManipulationBuffer::add($this->getFilePath(), [
                    new FileManipulation($branch_point, $branch_point, $var_id . ' = null;' . "\n" . $indentation),
                ]);
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public function checkUnreferencedVars()
    {
        $source = $this->getSource();
        $function_storage = $source instanceof FunctionLikeChecker ? $source->getFunctionLikeStorage($this) : null;

        foreach ($this->unused_var_locations as $hash => list($var_id, $original_location)) {
            if ($var_id === '$_' || isset($this->used_var_locations[$hash])) {
                continue;
            }

            if (!$function_storage || !array_key_exists(substr($var_id, 1), $function_storage->param_types)) {
                if (IssueBuffer::accepts(
                    new UnusedVariable(
                        'Variable ' . $var_id . ' is never referenced',
                        $original_location
                    ),
                    $this->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
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
                if (!is_string($var->var->name)) {
                    continue;
                }

                $var_id = '$' . $var->var->name;

                $context->vars_in_scope[$var_id] = Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;
                $context->assigned_var_ids[$var_id] = true;

                $location = new CodeLocation($this, $stmt);

                if ($context->collect_references) {
                    $context->unreferenced_vars[$var_id] = [$location->getHash() => $location];
                }

                $this->registerVariable(
                    $var_id,
                    $location,
                    $context->branch_point
                );
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Expr $stmt
     * @param   ?array<string, Type\Union> $existing_class_constants
     * @param   string $fq_classlike_name
     *
     * @return  Type\Union|null
     */
    public static function getSimpleType(
        \Psalm\Codebase $codebase,
        PhpParser\Node\Expr $stmt,
        \Psalm\Aliases $aliases,
        \Psalm\FileSource $file_source = null,
        array $existing_class_constants = null,
        $fq_classlike_name = null
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
                $codebase,
                $stmt->left,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );
            $stmt->right->inferredType = self::getSimpleType(
                $codebase,
                $stmt->right,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$stmt->left->inferredType || !$stmt->right->inferredType) {
                return null;
            }

            if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Plus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Minus ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mod ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Mul ||
                $stmt instanceof PhpParser\Node\Expr\BinaryOp\Pow
            ) {
                BinaryOpChecker::analyzeNonDivArithmenticOp(
                    $file_source instanceof StatementsSource ? $file_source : null,
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
                && $stmt->name instanceof PhpParser\Node\Identifier
                && $fq_classlike_name
                && $stmt->class->parts !== ['static']
                && $stmt->class->parts !== ['parent']
            ) {
                if (isset($existing_class_constants[$stmt->name->name])) {
                    if ($stmt->class->parts === ['self']) {
                        return clone $existing_class_constants[$stmt->name->name];
                    }
                }

                if ($stmt->class->parts === ['self']) {
                    $const_fq_class_name = $fq_classlike_name;
                } else {
                    $const_fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                        $stmt->class,
                        $aliases
                    );
                }

                if (strtolower($const_fq_class_name) === strtolower($fq_classlike_name)
                    && isset($existing_class_constants[$stmt->name->name])
                ) {
                    return clone $existing_class_constants[$stmt->name->name];
                }

                if (strtolower($stmt->name->name) === 'class') {
                    return Type::getClassString($const_fq_class_name);
                }

                if ($existing_class_constants === null) {
                    try {
                        $foreign_class_constants = $codebase->classlikes->getConstantsForClass(
                            $const_fq_class_name,
                            \ReflectionProperty::IS_PRIVATE
                        );

                        if (isset($foreign_class_constants[$stmt->name->name])) {
                            return clone $foreign_class_constants[$stmt->name->name];
                        }

                        return null;
                    } catch (\InvalidArgumentException $e) {
                        return null;
                    }
                }
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && strtolower($stmt->name->name) === 'class') {
                return Type::getClassString();
            }

            return null;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            return Type::getString(strlen($stmt->value) < 30 ? $stmt->value : null);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            return Type::getInt(false, $stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            return Type::getFloat($stmt->value);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (count($stmt->items) === 0) {
                return Type::getEmptyArray();
            }

            $item_key_type = null;
            $item_value_type = null;

            $property_types = [];
            $class_strings = [];

            $can_create_objectlike = true;

            foreach ($stmt->items as $int_offset => $item) {
                if ($item === null) {
                    continue;
                }

                if ($item->key) {
                    $single_item_key_type = self::getSimpleType(
                        $codebase,
                        $item->key,
                        $aliases,
                        $file_source,
                        $existing_class_constants,
                        $fq_classlike_name
                    );

                    if ($single_item_key_type) {
                        if ($item_key_type) {
                            $item_key_type = Type::combineUnionTypes($single_item_key_type, $item_key_type);
                        } else {
                            $item_key_type = $single_item_key_type;
                        }
                    }
                } else {
                    $item_key_type = Type::getInt();
                }

                if ($item_value_type && !$can_create_objectlike) {
                    continue;
                }

                $single_item_value_type = self::getSimpleType(
                    $codebase,
                    $item->value,
                    $aliases,
                    $file_source,
                    $existing_class_constants,
                    $fq_classlike_name
                );

                if (!$single_item_value_type) {
                    return null;
                }

                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    || $item->key instanceof PhpParser\Node\Scalar\LNumber
                    || !$item->key
                ) {
                    $property_types[$item->key ? $item->key->value : $int_offset] = $single_item_value_type;
                } else {
                    $dim_type = self::getSimpleType(
                        $codebase,
                        $item->key,
                        $aliases,
                        $file_source,
                        $existing_class_constants,
                        $fq_classlike_name
                    );

                    if (!$dim_type) {
                        return null;
                    }

                    $dim_atomic_types = $dim_type->getTypes();

                    if (count($dim_atomic_types) > 1 || $dim_type->isMixed()) {
                        $can_create_objectlike = false;
                    } else {
                        $atomic_type = array_shift($dim_atomic_types);

                        if ($atomic_type instanceof Type\Atomic\TLiteralInt
                            || $atomic_type instanceof Type\Atomic\TLiteralString
                        ) {
                            if ($atomic_type instanceof Type\Atomic\TLiteralClassString) {
                                $class_strings[$atomic_type->value] = true;
                            }

                            $property_types[$atomic_type->value] = $single_item_value_type;
                        } else {
                            $can_create_objectlike = false;
                        }
                    }
                }

                if ($item_value_type) {
                    $item_value_type = Type::combineUnionTypes($single_item_value_type, $item_value_type);
                } else {
                    $item_value_type = $single_item_value_type;
                }
            }

            // if this array looks like an object-like array, let's return that instead
            if ($item_value_type
                && $item_key_type
                && ($item_key_type->hasString() || $item_key_type->hasInt())
                && $can_create_objectlike
            ) {
                return new Type\Union([new Type\Atomic\ObjectLike($property_types, $class_strings)]);
            }

            if (!$item_key_type || !$item_value_type) {
                return null;
            }

            return new Type\Union([
                new Type\Atomic\TArray([
                    $item_key_type,
                    $item_value_type,
                ]),
            ]);
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
            $type_to_invert = self::getSimpleType(
                $codebase,
                $stmt->expr,
                $aliases,
                $file_source,
                $existing_class_constants,
                $fq_classlike_name
            );

            if (!$type_to_invert) {
                return null;
            }

            foreach ($type_to_invert->getTypes() as $type_part) {
                if ($type_part instanceof Type\Atomic\TLiteralInt
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                } elseif ($type_part instanceof Type\Atomic\TLiteralFloat
                    && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                ) {
                    $type_part->value = -$type_part->value;
                }
            }

            return $type_to_invert;
        }

        return null;
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
                $const->name->name,
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
            $fq_const_name = Type::getFQCLNFromString($const_name, $this->getAliases());
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

        if ($context->hasVariable($const_name, $statements_checker)) {
            return $context->vars_in_scope[$const_name];
        }

        $file_path = $statements_checker->getRootFilePath();
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $file_storage_provider = $project_checker->file_storage_provider;

        $file_storage = $file_storage_provider->get($file_path);

        if (isset($file_storage->declaring_constants[$const_name])) {
            $constant_file_path = $file_storage->declaring_constants[$const_name];

            return $file_storage_provider->get($constant_file_path)->constants[$const_name];
        }

        return ConstFetchChecker::getGlobalConstType($project_checker->codebase, $fq_const_name, $const_name);
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
     * @param  string       $var_name
     *
     * @return bool
     */
    public function hasVariable($var_name)
    {
        return isset($this->all_vars[$var_name]);
    }

    /**
     * @param  string       $var_id
     * @param  CodeLocation $location
     * @param  int|null     $branch_point
     *
     * @return void
     */
    public function registerVariable($var_id, CodeLocation $location, $branch_point)
    {
        $this->all_vars[$var_id] = $location;

        if ($branch_point) {
            $this->var_branch_points[$var_id] = $branch_point;
        }

        $this->registerVariableAssignment($var_id, $location);
    }

    /**
     * @param  string       $var_id
     * @param  CodeLocation $location
     *
     * @return void
     */
    public function registerVariableAssignment($var_id, CodeLocation $location)
    {
        $this->unused_var_locations[$location->getHash()] = [$var_id, $location];
    }

    /**
     * @param array<string, CodeLocation> $locations
     * @return void
     */
    public function registerVariableUses(array $locations)
    {
        foreach ($locations as $hash => $_) {
            unset($this->unused_var_locations[$hash]);
            $this->used_var_locations[$hash] = true;
        }
    }

    /**
     * @return array<string, array{0: string, 1: CodeLocation}>
     */
    public function getUnusedVarLocations()
    {
        return $this->unused_var_locations;
    }

    /**
     * The first appearance of the variable in this set of statements being evaluated
     *
     * @param  string  $var_id
     *
     * @return CodeLocation|null
     */
    public function getFirstAppearance($var_id)
    {
        return isset($this->all_vars[$var_id]) ? $this->all_vars[$var_id] : null;
    }

    /**
     * @param  string $var_id
     *
     * @return int|null
     */
    public function getBranchPoint($var_id)
    {
        return isset($this->var_branch_points[$var_id]) ? $this->var_branch_points[$var_id] : null;
    }

    /**
     * @param string $var_id
     * @param int    $branch_point
     *
     * @return void
     */
    public function addVariableInitialization($var_id, $branch_point)
    {
        $this->vars_to_initialize[$var_id] = $branch_point;
    }

    /**
     * @return FileChecker
     */
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
}
