<?php
namespace Psalm\Checker\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TraitChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;

class ConstFetchChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\ConstFetch  $stmt
     * @param   Context                         $context
     *
     * @return  void
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ConstFetch $stmt,
        Context $context
    ) {
        $const_name = implode('\\', $stmt->name->parts);
        switch (strtolower($const_name)) {
            case 'null':
                $stmt->inferredType = Type::getNull();
                break;

            case 'false':
                // false is a subtype of bool
                $stmt->inferredType = Type::getFalse();
                break;

            case 'true':
                $stmt->inferredType = Type::getTrue();
                break;

            case 'stdin':
                $stmt->inferredType = Type::getResource();
                break;

            default:
                $const_type = $statements_checker->getConstType(
                    $statements_checker,
                    $const_name,
                    $stmt->name instanceof PhpParser\Node\Name\FullyQualified,
                    $context
                );

                if ($const_type) {
                    $stmt->inferredType = clone $const_type;
                } elseif ($context->check_consts) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Const ' . $const_name . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
        }
    }

    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ClassConstFetch $stmt
     * @param   Context                             $context
     *
     * @return  null|false
     */
    public static function analyzeClassConst(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        if ($context->check_consts &&
            $stmt->class instanceof PhpParser\Node\Name &&
            is_string($stmt->name)
        ) {
            $first_part_lc = strtolower($stmt->class->parts[0]);

            if ($first_part_lc === 'self' || $first_part_lc === 'static') {
                if (!$context->self) {
                    throw new \UnexpectedValueException('$context->self cannot be null');
                }

                $fq_class_name = (string)$context->self;
            } elseif ($first_part_lc === 'parent') {
                $fq_class_name = $statements_checker->getParentFQCLN();

                if ($fq_class_name === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }
            } else {
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

            if ($stmt->name === 'class') {
                $stmt->inferredType = Type::getClassString();

                return null;
            }

            $project_checker = $statements_checker->getFileChecker()->project_checker;
            $codebase = $project_checker->codebase;

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classOrInterfaceExists($fq_class_name)) {
                $stmt->inferredType = Type::getMixed();

                return null;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($fq_class_name === $context->self
                || (
                    $statements_checker->getSource()->getSource() instanceof TraitChecker &&
                    $fq_class_name === $statements_checker->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                $codebase->classExtends($context->self, $fq_class_name)
            ) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            $class_constants = $codebase->classlikes->getConstantsForClass(
                $fq_class_name,
                $class_visibility
            );

            if (!isset($class_constants[$stmt->name]) && $first_part_lc !== 'static') {
                $all_class_constants = [];

                if ($fq_class_name !== $context->self) {
                    $all_class_constants = $codebase->classlikes->getConstantsForClass(
                        $fq_class_name,
                        \ReflectionProperty::IS_PRIVATE
                    );
                }

                if ($all_class_constants && isset($all_class_constants[$stmt->name])) {
                    IssueBuffer::add(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        )
                    );
                } else {
                    IssueBuffer::add(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        )
                    );
                }

                return false;
            }
            $stmt->inferredType = isset($class_constants[$stmt->name])
                && $first_part_lc !== 'static'
                ? $class_constants[$stmt->name]
                : Type::getMixed();

            return null;
        }

        $stmt->inferredType = Type::getMixed();

        if ($stmt->class instanceof PhpParser\Node\Expr) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->class, $context) === false) {
                return false;
            }
        }

        return null;
    }
}
