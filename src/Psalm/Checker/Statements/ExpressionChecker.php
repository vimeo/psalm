<?php
namespace Psalm\Checker\Statements;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ClosureChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\Statements\Expression\ArrayChecker;
use Psalm\Checker\Statements\Expression\AssignmentChecker;
use Psalm\Checker\Statements\Expression\BinaryOpChecker;
use Psalm\Checker\Statements\Expression\Call\FunctionCallChecker;
use Psalm\Checker\Statements\Expression\Call\MethodCallChecker;
use Psalm\Checker\Statements\Expression\Call\NewChecker;
use Psalm\Checker\Statements\Expression\Call\StaticCallChecker;
use Psalm\Checker\Statements\Expression\Fetch\ArrayFetchChecker;
use Psalm\Checker\Statements\Expression\Fetch\ConstFetchChecker;
use Psalm\Checker\Statements\Expression\Fetch\PropertyFetchChecker;
use Psalm\Checker\Statements\Expression\Fetch\VariableFetchChecker;
use Psalm\Checker\Statements\Expression\IncludeChecker;
use Psalm\Checker\Statements\Expression\TernaryChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidCast;
use Psalm\Issue\InvalidClone;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;

class ExpressionChecker
{
    /**
     * @param   StatementsChecker   $statements_checker
     * @param   PhpParser\Node\Expr $stmt
     * @param   Context             $context
     * @param   bool                $array_assignment
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        $array_assignment = false
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            if (VariableFetchChecker::analyze(
                $statements_checker,
                $stmt,
                $context,
                false,
                null,
                $array_assignment
            ) === false
            ) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $assignment_type = AssignmentChecker::analyze(
                $statements_checker,
                $stmt->var,
                $stmt->expr,
                null,
                $context,
                (string)$stmt->getDocComment(),
                $stmt->getLine()
            );

            if ($assignment_type === false) {
                return false;
            }

            $stmt->inferredType = $assignment_type;
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            if (AssignmentChecker::analyzeAssignmentOperation($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            if (MethodCallChecker::analyze($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            if (StaticCallChecker::analyze($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            ConstFetchChecker::analyze($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $stmt->inferredType = Type::getString();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            switch (strtolower($stmt->getName())) {
                case '__line__':
                    $stmt->inferredType = Type::getInt();
                    break;

                case '__file__':
                case '__dir__':
                case '__function__':
                case '__class__':
                case '__trait__':
                case '__method__':
                case '__namespace__':
                    $stmt->inferredType = Type::getString();
                    break;
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $stmt->inferredType = Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $stmt->inferredType = Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\UnaryMinus ||
            $stmt instanceof PhpParser\Node\Expr\UnaryPlus
        ) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            if (!isset($stmt->expr->inferredType)) {
                $stmt->inferredType = new Type\Union([new TInt, new TFloat]);
            } elseif ($stmt->expr->inferredType->isMixed()) {
                $stmt->inferredType = Type::getMixed();
            } else {
                $acceptable_types = [];

                foreach ($stmt->expr->inferredType->getTypes() as $type_part) {
                    if ($type_part instanceof TInt || $type_part instanceof TFloat) {
                        $acceptable_types[] = $type_part;
                    } elseif ($type_part instanceof TString) {
                        $acceptable_types[] = new TInt;
                        $acceptable_types[] = new TFloat;
                    } else {
                        $acceptable_types[] = new TInt;
                    }
                }

                $stmt->inferredType = new Type\Union($acceptable_types);
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            self::analyzeIsset($statements_checker, $stmt, $context);
            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            if (ConstFetchChecker::analyzeClassConst($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            if (PropertyFetchChecker::analyzeInstance($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            if (PropertyFetchChecker::analyzeStatic($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            if (BinaryOpChecker::analyze(
                $statements_checker,
                $stmt,
                $context
            ) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\PostInc ||
            $stmt instanceof PhpParser\Node\Expr\PostDec ||
            $stmt instanceof PhpParser\Node\Expr\PreInc ||
            $stmt instanceof PhpParser\Node\Expr\PreDec
        ) {
            if (self::analyze($statements_checker, $stmt->var, $context) === false) {
                return false;
            }

            if (isset($stmt->var->inferredType)) {
                $stmt->inferredType = clone $stmt->var->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\New_) {
            if (NewChecker::analyze($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Array_) {
            if (ArrayChecker::analyze($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            if (self::analyzeEncapsulatedString($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            $project_checker = $statements_checker->getFileChecker()->project_checker;
            if (FunctionCallChecker::analyze(
                $project_checker,
                $statements_checker,
                $stmt,
                $context
            ) === false
            ) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            if (TernaryChecker::analyze($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            if (self::analyzeBooleanNot($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            self::analyzeEmpty($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Closure) {
            $closure_checker = new ClosureChecker($stmt, $statements_checker->getSource());

            if (self::analyzeClosureUses($statements_checker, $stmt, $context) === false) {
                return false;
            }

            $codebase = $statements_checker->getFileChecker()->project_checker->codebase;

            $use_context = new Context($context->self);
            $use_context->collect_references = $codebase->collect_references;

            if (!$statements_checker->isStatic()) {
                if ($context->collect_mutations &&
                    $context->self &&
                    $codebase->classExtends(
                        $context->self,
                        (string)$statements_checker->getFQCLN()
                    )
                ) {
                    $use_context->vars_in_scope['$this'] = clone $context->vars_in_scope['$this'];
                } elseif ($context->self) {
                    $use_context->vars_in_scope['$this'] = new Type\Union([new TNamedObject($context->self)]);
                }
            }

            foreach ($context->vars_in_scope as $var => $type) {
                if (strpos($var, '$this->') === 0) {
                    $use_context->vars_in_scope[$var] = clone $type;
                }
            }

            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (strpos($var, '$this->') === 0) {
                    $use_context->vars_possibly_in_scope[$var] = true;
                }
            }

            foreach ($stmt->uses as $use) {
                // insert the ref into the current context if passed by ref, as whatever we're passing
                // the closure to could execute it straight away.
                if (!$context->hasVariable('$' . $use->var, $statements_checker) && $use->byRef) {
                    $context->vars_in_scope['$' . $use->var] = Type::getMixed();
                }

                $use_context->vars_in_scope['$' . $use->var] =
                    $context->hasVariable('$' . $use->var, $statements_checker) && !$use->byRef
                    ? clone $context->vars_in_scope['$' . $use->var]
                    : Type::getMixed();

                $use_context->vars_possibly_in_scope['$' . $use->var] = true;
            }

            $closure_checker->analyze($use_context, $context);

            if (!isset($stmt->inferredType)) {
                $stmt->inferredType = Type::getClosure();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            if (ArrayFetchChecker::analyze(
                $statements_checker,
                $stmt,
                $context
            ) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Int_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getInt();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Double) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getFloat();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Bool_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\String_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $container_type = Type::getString();

            if (isset($stmt->expr->inferredType)
                && !$stmt->expr->inferredType->isMixed()
                && !TypeChecker::isContainedBy(
                    $statements_checker->getFileChecker()->project_checker->codebase,
                    $stmt->expr->inferredType,
                    $container_type,
                    true,
                    false,
                    $has_scalar_match
                )
                && !$has_scalar_match
            ) {
                if (IssueBuffer::accepts(
                    new InvalidCast(
                        $stmt->expr->inferredType . ' cannot be cast to ' . $container_type,
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            $stmt->inferredType = $container_type;
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Object_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = new Type\Union([new TNamedObject('stdClass')]);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Array_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $permissible_atomic_types = [];
            $all_permissible = false;

            if (isset($stmt->expr->inferredType)) {
                $all_permissible = true;

                foreach ($stmt->expr->inferredType->getTypes() as $type) {
                    if ($type instanceof Scalar) {
                        $permissible_atomic_types[] = new TArray([Type::getInt(), new Type\Union([$type])]);
                    } elseif ($type instanceof TArray) {
                        $permissible_atomic_types[] = $type;
                    } elseif ($type instanceof ObjectLike) {
                        $permissible_atomic_types[] = $type->getGenericArrayType();
                    } else {
                        $all_permissible = false;
                        break;
                    }
                }
            }

            if ($all_permissible) {
                $stmt->inferredType = Type::combineTypes($permissible_atomic_types);
            } else {
                $stmt->inferredType = Type::getArray();
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Cast\Unset_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            $stmt->inferredType = Type::getNull();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            self::analyzeClone($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }

            if ($stmt->class instanceof PhpParser\Node\Name &&
                !in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($context->check_classes) {
                    $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                        $stmt->class,
                        $statements_checker->getAliases()
                    );

                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
                    }
                }
            }

            $stmt->inferredType = Type::getBool();
        } elseif ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            if ($stmt->expr) {
                if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                    return false;
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Include_) {
            IncludeChecker::analyze($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            $context->check_classes = false;
            $context->check_variables = false;

            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if (AssignmentChecker::analyzeAssignmentRef($statements_checker, $stmt, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            // do nothing
        } elseif ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if (IssueBuffer::accepts(
                new ForbiddenCode(
                    'Use of shell_exec',
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Print_) {
            if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
                return false;
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            self::analyzeYield($statements_checker, $stmt, $context);
        } elseif ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            self::analyzeYieldFrom($statements_checker, $stmt, $context);
        } else {
            if (IssueBuffer::accepts(
                new UnrecognizedExpression(
                    'Psalm does not understand ' . get_class($stmt),
                    new CodeLocation($statements_checker->getSource(), $stmt)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $plugin_method_ids = $project_checker->config->after_expression_checks;

        if ($plugin_method_ids) {
            $file_manipulations = [];
            $code_location = new CodeLocation($statements_checker->getSource(), $stmt);

            foreach ($plugin_method_ids as $plugin_method_id) {
                if ($plugin_method_id(
                    $statements_checker,
                    $stmt,
                    $context,
                    $code_location,
                    $statements_checker->getSuppressedIssues(),
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                /** @psalm-suppress MixedTypeCoercion */
                FileManipulationBuffer::add($statements_checker->getFilePath(), $file_manipulations);
            }
        }

        return null;
    }

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Type\Union           $by_ref_type
     * @param  bool                 $constrain_type
     * @param  Context              $context
     *
     * @return void
     */
    public static function assignByRefParam(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Type\Union $by_ref_type,
        Context $context,
        $constrain_type = true
    ) {
        $var_id = self::getVarId(
            $stmt,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        if ($var_id) {
            if (!$by_ref_type->isMixed() && $constrain_type) {
                $context->byref_constraints[$var_id] = new \Psalm\ReferenceConstraint($by_ref_type);
            }

            if (!$context->hasVariable($var_id, $statements_checker)) {
                $context->vars_possibly_in_scope[$var_id] = true;

                if (!$statements_checker->hasVariable($var_id)) {
                    $location = new CodeLocation($statements_checker, $stmt);
                    $statements_checker->registerVariable($var_id, $location, null);

                    if ($context->collect_references) {
                        $context->unreferenced_vars[$var_id] = $location;
                    }

                    $context->hasVariable($var_id, $statements_checker);
                }
            } else {
                $existing_type = $context->vars_in_scope[$var_id];

                // removes dependennt vars from $context
                $context->removeDescendents(
                    $var_id,
                    $existing_type,
                    $by_ref_type,
                    $statements_checker
                );

                if ($existing_type->getId() !== 'array<empty, empty>') {
                    $context->vars_in_scope[$var_id] = $by_ref_type;
                    $stmt->inferredType = $context->vars_in_scope[$var_id];

                    return;
                }
            }

            $context->vars_in_scope[$var_id] = $by_ref_type;
        }

        $stmt->inferredType = $by_ref_type;
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource|null    $source
     * @param  int|null                 &$nesting
     *
     * @return string|null
     */
    public static function getVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source = null,
        &$nesting = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable && is_string($stmt->name)) {
            return '$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && is_string($stmt->name)
            && $stmt->class instanceof PhpParser\Node\Name
        ) {
            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if (!$this_class_name) {
                    $fq_class_name = $stmt->class->parts[0];
                } else {
                    $fq_class_name = $this_class_name;
                }
            } else {
                $fq_class_name = $source
                    ? ClassLikeChecker::getFQCLNFromNameObject(
                        $stmt->class,
                        $source->getAliases()
                    )
                    : implode('\\', $stmt->class->parts);
            }

            return $fq_class_name . '::$' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && is_string($stmt->name)) {
            $object_id = self::getVarId($stmt->var, $this_class_name, $source);

            if (!$object_id) {
                return null;
            }

            return $object_id . '->' . $stmt->name;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch && $nesting !== null) {
            ++$nesting;

            return self::getVarId($stmt->var, $this_class_name, $source, $nesting);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource|null    $source
     *
     * @return string|null
     */
    public static function getRootVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Variable
            || $stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
        ) {
            return self::getVarId($stmt, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch && is_string($stmt->name)) {
            $property_root = self::getRootVarId($stmt->var, $this_class_name, $source);

            if ($property_root) {
                return $property_root . '->' . $stmt->name;
            }
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return self::getRootVarId($stmt->var, $this_class_name, $source);
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Expr      $stmt
     * @param  string|null              $this_class_name
     * @param  StatementsSource|null    $source
     *
     * @return string|null
     */
    public static function getArrayVarId(
        PhpParser\Node\Expr $stmt,
        $this_class_name,
        StatementsSource $source = null
    ) {
        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::getArrayVarId($stmt->var, $this_class_name, $source);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch &&
            ($stmt->dim instanceof PhpParser\Node\Scalar\String_ ||
                $stmt->dim instanceof PhpParser\Node\Scalar\LNumber)
        ) {
            $root_var_id = self::getArrayVarId($stmt->var, $this_class_name, $source);
            $offset = $stmt->dim instanceof PhpParser\Node\Scalar\String_
                ? '\'' . $stmt->dim->value . '\''
                : $stmt->dim->value;

            return $root_var_id ? $root_var_id . '[' . $offset . ']' : null;
        }

        return self::getVarId($stmt, $this_class_name, $source);
    }

    /**
     * @param  Type\Union   $return_type
     * @param  string|null  $self_class
     * @param  string|null  $static_class
     *
     * @return Type\Union
     */
    public static function fleshOutType(
        ProjectChecker $project_checker,
        Type\Union $return_type,
        $self_class = null,
        $static_class = null
    ) {
        $return_type = clone $return_type;

        $new_return_type_parts = [];

        foreach ($return_type->getTypes() as $return_type_part) {
            $new_return_type_parts[] = self::fleshOutAtomicType(
                $project_checker,
                $return_type_part,
                $self_class,
                $static_class
            );
        }

        $fleshed_out_type = new Type\Union($new_return_type_parts);

        $fleshed_out_type->from_docblock = $return_type->from_docblock;
        $fleshed_out_type->ignore_nullable_issues = $return_type->ignore_nullable_issues;
        $fleshed_out_type->ignore_falsable_issues = $return_type->ignore_falsable_issues;
        $fleshed_out_type->by_ref = $return_type->by_ref;

        return $fleshed_out_type;
    }

    /**
     * @param  Type\Atomic  &$return_type
     * @param  string|null  $self_class
     * @param  string|null  $static_class
     *
     * @return Type\Atomic
     */
    private static function fleshOutAtomicType(
        ProjectChecker $project_checker,
        Type\Atomic $return_type,
        $self_class,
        $static_class
    ) {
        if ($return_type instanceof TNamedObject) {
            $return_type_lc = strtolower($return_type->value);

            if ($return_type_lc === 'static' || $return_type_lc === '$this') {
                if (!$static_class) {
                    throw new \InvalidArgumentException(
                        'Cannot handle ' . $return_type->value . ' when $static_class is empty'
                    );
                }

                $return_type->value = $static_class;
            } elseif ($return_type_lc === 'self') {
                if (!$self_class) {
                    throw new \InvalidArgumentException(
                        'Cannot handle ' . $return_type->value . ' when $self_class is empty'
                    );
                }

                $return_type->value = $self_class;
            }
        }

        if ($return_type instanceof Type\Atomic\TArray || $return_type instanceof Type\Atomic\TGenericObject) {
            foreach ($return_type->type_params as &$type_param) {
                $type_param = self::fleshOutType(
                    $project_checker,
                    $type_param,
                    $self_class,
                    $static_class
                );
            }
        }

        return $return_type;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Closure $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeClosureUses(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context
    ) {
        foreach ($stmt->uses as $use) {
            $use_var_id = '$' . $use->var;
            if (!$context->hasVariable($use_var_id, $statements_checker)) {
                if ($use->byRef) {
                    $context->vars_in_scope[$use_var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$use_var_id] = true;

                    if (!$statements_checker->hasVariable($use_var_id)) {
                        $statements_checker->registerVariable(
                            $use_var_id,
                            new CodeLocation($statements_checker, $use),
                            null
                        );
                    }

                    return;
                }

                if (!isset($context->vars_possibly_in_scope[$use_var_id])) {
                    if ($context->check_variables) {
                        if (IssueBuffer::accepts(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $use_var_id,
                                new CodeLocation($statements_checker->getSource(), $use)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }
                }

                $first_appearance = $statements_checker->getFirstAppearance($use_var_id);

                if ($first_appearance) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_checker->getSource(), $use)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($context->check_variables) {
                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $use_var_id,
                            new CodeLocation($statements_checker->getSource(), $use)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Yield_  $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    protected static function analyzeYield(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Yield_ $stmt,
        Context $context
    ) {
        $doc_comment_text = (string)$stmt->getDocComment();

        $var_comments = [];
        $var_comment_type = null;

        if ($doc_comment_text) {
            try {
                $var_comments = CommentChecker::getTypeFromComment(
                    $doc_comment_text,
                    $statements_checker,
                    $statements_checker->getAliases()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    )
                )) {
                    // fall through
                }
            }

            foreach ($var_comments as $var_comment) {
                $comment_type = ExpressionChecker::fleshOutType(
                    $statements_checker->getFileChecker()->project_checker,
                    $var_comment->type,
                    $context->self,
                    $context->self
                );

                if (!$var_comment->var_id) {
                    $var_comment_type = $comment_type;
                    continue;
                }

                $context->vars_in_scope[$var_comment->var_id] = $comment_type;
            }
        }

        if ($stmt->key) {
            if (self::analyze($statements_checker, $stmt->key, $context) === false) {
                return false;
            }
        }

        if ($stmt->value) {
            if (self::analyze($statements_checker, $stmt->value, $context) === false) {
                return false;
            }

            if ($var_comment_type) {
                $stmt->inferredType = $var_comment_type;
            } elseif (isset($stmt->value->inferredType)) {
                $stmt->inferredType = $stmt->value->inferredType;
            } else {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $stmt->inferredType = Type::getNull();
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\YieldFrom   $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeYieldFrom(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context
    ) {
        if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            // this should be whatever the generator above returns, but *not* the return type
            $stmt->inferredType = Type::getMixed();
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\BooleanNot  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeBooleanNot(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ) {
        $stmt->inferredType = Type::getBool();

        if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\Empty_  $stmt
     * @param   Context                     $context
     *
     * @return  void
     */
    protected static function analyzeEmpty(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Empty_ $stmt,
        Context $context
    ) {
        self::analyzeIssetVar($statements_checker, $stmt->expr, $context);
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Scalar\Encapsed  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    protected static function analyzeEncapsulatedString(
        StatementsChecker $statements_checker,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $function_storage = null;

        if ($project_checker->infer_types_from_usage) {
            $source_checker = $statements_checker->getSource();

            if ($source_checker instanceof FunctionLikeChecker) {
                $function_storage = $source_checker->getFunctionLikeStorage($statements_checker);
            }
        }

        /** @var PhpParser\Node\Expr $part */
        foreach ($stmt->parts as $part) {
            if (self::analyze($statements_checker, $part, $context) === false) {
                return false;
            }

            if ($function_storage) {
                $context->inferType($part, $function_storage, Type::getString());
            }
        }

        $stmt->inferredType = Type::getString();

        return null;
    }

    /**
     * @param  StatementsChecker          $statements_checker
     * @param  PhpParser\Node\Expr\Isset_ $stmt
     * @param  Context                    $context
     *
     * @return void
     */
    protected static function analyzeIsset(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Isset_ $stmt,
        Context $context
    ) {
        foreach ($stmt->vars as $isset_var) {
            if ($isset_var instanceof PhpParser\Node\Expr\PropertyFetch &&
                $isset_var->var instanceof PhpParser\Node\Expr\Variable &&
                $isset_var->var->name === 'this' &&
                is_string($isset_var->name)
            ) {
                $var_id = '$this->' . $isset_var->name;

                if (!isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            }

            self::analyzeIssetVar($statements_checker, $isset_var, $context);
        }
    }

    /**
     * @param  StatementsChecker   $statements_checker
     * @param  PhpParser\Node\Expr $stmt
     * @param  Context             $context
     *
     * @return false|null
     */
    protected static function analyzeIssetVar(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context
    ) {
        $context->inside_isset = true;

        if (self::analyze($statements_checker, $stmt, $context) === false) {
            return false;
        }

        $context->inside_isset = false;
    }

    /**
     * @param  StatementsChecker            $statements_checker
     * @param  PhpParser\Node\Expr\Clone_   $stmt
     * @param  Context                      $context
     *
     * @return false|null
     */
    protected static function analyzeClone(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context
    ) {
        if (self::analyze($statements_checker, $stmt->expr, $context) === false) {
            return false;
        }

        if (isset($stmt->expr->inferredType)) {
            foreach ($stmt->expr->inferredType->getTypes() as $clone_type_part) {
                if (!$clone_type_part instanceof TNamedObject &&
                    !$clone_type_part instanceof TObject &&
                    !$clone_type_part instanceof TMixed
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidClone(
                            'Cannot clone ' . $clone_type_part,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }
            }

            $stmt->inferredType = $stmt->expr->inferredType;
        }
    }

    /**
     * @param  string  $fq_class_name
     *
     * @return bool
     */
    public static function isMock($fq_class_name)
    {
        return in_array($fq_class_name, Config::getInstance()->getMockClasses(), true);
    }
}
