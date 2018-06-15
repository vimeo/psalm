<?php
namespace Psalm\Checker\FunctionLike;

use PhpParser;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\InterfaceChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation\FunctionDocblockManipulator;
use Psalm\Issue\InvalidFalsableReturnType;
use Psalm\Issue\InvalidNullableReturnType;
use Psalm\Issue\InvalidReturnType;
use Psalm\Issue\InvalidToString;
use Psalm\Issue\LessSpecificReturnType;
use Psalm\Issue\MismatchingDocblockReturnType;
use Psalm\Issue\MissingClosureReturnType;
use Psalm\Issue\MissingReturnType;
use Psalm\Issue\MixedInferredReturnType;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\MoreSpecificReturnType;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\TypeCombination;

class ReturnTypeChecker
{
    /**
     * @param Closure|Function_|ClassMethod $function
     * @param StatementsSource    $source
     * @param Type\Union|null     $return_type
     * @param string              $fq_class_name
     * @param CodeLocation|null   $return_type_location
     * @param string[]            $compatible_method_ids
     *
     * @return  false|null
     */
    public static function verifyReturnType(
        FunctionLike $function,
        StatementsSource $source,
        FunctionLikeChecker $function_like_checker,
        Type\Union $return_type = null,
        $fq_class_name = null,
        CodeLocation $return_type_location = null,
        array $compatible_method_ids = []
    ) {
        $suppressed_issues = $function_like_checker->getSuppressedIssues();
        $project_checker = $source->getFileChecker()->project_checker;

        if (!$function->getStmts() &&
            (
                $function instanceof ClassMethod &&
                ($source instanceof InterfaceChecker || $function->isAbstract())
            )
        ) {
            return null;
        }

        $is_to_string = $function instanceof ClassMethod && strtolower($function->name) === '__tostring';

        if ($function instanceof ClassMethod
            && substr($function->name, 0, 2) === '__'
            && !$is_to_string
            && !$return_type
        ) {
            // do not check __construct, __set, __get, __call etc.
            return null;
        }

        $cased_method_id = $function_like_checker->getCorrectlyCasedMethodId();

        if (!$return_type_location) {
            $return_type_location = new CodeLocation($function_like_checker, $function, null, true);
        }

        $inferred_yield_types = [];

        /** @var PhpParser\Node\Stmt[] */
        $function_stmts = $function->getStmts();

        $inferred_return_type_parts = ReturnTypeCollector::getReturnTypes(
            $function_stmts,
            $inferred_yield_types,
            $ignore_nullable_issues,
            $ignore_falsable_issues,
            true
        );

        if ((!$return_type || $return_type->from_docblock)
            && ScopeChecker::getFinalControlActions(
                $function_stmts,
                $project_checker->config->exit_functions
            ) !== [ScopeChecker::ACTION_END]
            && !$inferred_yield_types
            && count($inferred_return_type_parts)
        ) {
            // only add null if we have a return statement elsewhere and it wasn't void
            foreach ($inferred_return_type_parts as $inferred_return_type_part) {
                if (!$inferred_return_type_part instanceof Type\Atomic\TVoid) {
                    $atomic_null = new Type\Atomic\TNull();
                    $atomic_null->from_docblock = true;
                    $inferred_return_type_parts[] = $atomic_null;
                    break;
                }
            }
        }

        if ($return_type
            && !$return_type->from_docblock
            && !$return_type->isVoid()
            && !$inferred_yield_types
            && ScopeChecker::getFinalControlActions(
                $function_stmts,
                $project_checker->config->exit_functions
            ) !== [ScopeChecker::ACTION_END]
        ) {
            if (IssueBuffer::accepts(
                new InvalidReturnType(
                    'Not all code paths of ' . $cased_method_id . ' end in a return statement, return type '
                        . $return_type . ' expected',
                    $return_type_location
                )
            )) {
                return false;
            }

            return null;
        }

        $inferred_return_type = $inferred_return_type_parts
            ? TypeCombination::combineTypes($inferred_return_type_parts)
            : Type::getVoid();
        $inferred_yield_type = $inferred_yield_types ? TypeCombination::combineTypes($inferred_yield_types) : null;

        if ($inferred_yield_type) {
            $inferred_return_type = $inferred_yield_type;
        }

        $codebase = $project_checker->codebase;

        if (!$return_type && !$codebase->config->add_void_docblocks && $inferred_return_type->isVoid()) {
            return null;
        }

        $unsafe_return_type = false;

        // prevent any return types that do not return a value from being used in PHP typehints
        if ($project_checker->alter_code
            && $inferred_return_type->isNullable()
            && !$inferred_yield_types
        ) {
            foreach ($inferred_return_type_parts as $inferred_return_type_part) {
                if ($inferred_return_type_part instanceof Type\Atomic\TVoid) {
                    $unsafe_return_type = true;
                }
            }
        }

        $inferred_return_type = TypeChecker::simplifyUnionType(
            $codebase,
            ExpressionChecker::fleshOutType(
                $project_checker,
                $inferred_return_type,
                $source->getFQCLN(),
                $source->getFQCLN()
            )
        );

        if ($is_to_string) {
            if (!$inferred_return_type->isMixed() &&
                !TypeChecker::isContainedBy(
                    $codebase,
                    $inferred_return_type,
                    Type::getString(),
                    $inferred_return_type->ignore_nullable_issues,
                    $inferred_return_type->ignore_falsable_issues,
                    $has_scalar_match,
                    $type_coerced,
                    $type_coerced_from_mixed
                )
            ) {
                if (IssueBuffer::accepts(
                    new InvalidToString(
                        '__toString methods must return a string, ' . $inferred_return_type . ' returned',
                        $return_type_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }

            return null;
        }

        if (!$return_type) {
            if ($function instanceof Closure) {
                if ($project_checker->alter_code
                    && isset($project_checker->getIssuesToFix()['MissingClosureReturnType'])
                ) {
                    if ($inferred_return_type->isMixed() || $inferred_return_type->isNull()) {
                        return null;
                    }

                    self::addOrUpdateReturnType(
                        $function,
                        $project_checker,
                        $inferred_return_type,
                        $source,
                        $function_like_checker,
                        ($project_checker->only_replace_php_types_with_non_docblock_types
                            || $unsafe_return_type)
                            && $inferred_return_type->from_docblock
                    );

                    return null;
                }

                if (IssueBuffer::accepts(
                    new MissingClosureReturnType(
                        'Closure does not have a return type, expecting ' . $inferred_return_type,
                        new CodeLocation($function_like_checker, $function, null, true)
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }

                return null;
            }

            if ($project_checker->alter_code
                && isset($project_checker->getIssuesToFix()['MissingReturnType'])
            ) {
                if ($inferred_return_type->isMixed() || $inferred_return_type->isNull()) {
                    return null;
                }

                self::addOrUpdateReturnType(
                    $function,
                    $project_checker,
                    $inferred_return_type,
                    $source,
                    $function_like_checker,
                    $compatible_method_ids
                    || (($project_checker->only_replace_php_types_with_non_docblock_types
                            || $unsafe_return_type)
                        && $inferred_return_type->from_docblock)
                );

                return null;
            }

            if (IssueBuffer::accepts(
                new MissingReturnType(
                    'Method ' . $cased_method_id . ' does not have a return type' .
                      (!$inferred_return_type->isMixed() ? ', expecting ' . $inferred_return_type : ''),
                    new CodeLocation($function_like_checker, $function, null, true)
                ),
                $suppressed_issues
            )) {
                // fall through
            }

            return null;
        }

        $self_fq_class_name = $fq_class_name ?: $source->getFQCLN();

        // passing it through fleshOutTypes eradicates errant $ vars
        $declared_return_type = ExpressionChecker::fleshOutType(
            $project_checker,
            $return_type,
            $self_fq_class_name,
            $self_fq_class_name
        );

        if (!$inferred_return_type_parts && !$inferred_yield_types) {
            if ($declared_return_type->isVoid()) {
                return null;
            }

            if (ScopeChecker::onlyThrows($function_stmts)) {
                // if there's a single throw statement, it's presumably an exception saying this method is not to be
                // used
                return null;
            }

            if ($project_checker->alter_code && isset($project_checker->getIssuesToFix()['InvalidReturnType'])) {
                self::addOrUpdateReturnType(
                    $function,
                    $project_checker,
                    Type::getVoid(),
                    $source,
                    $function_like_checker
                );

                return null;
            }

            if (!$declared_return_type->from_docblock || !$declared_return_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new InvalidReturnType(
                        'No return statements were found for method ' . $cased_method_id .
                            ' but return type \'' . $declared_return_type . '\' was expected',
                        $return_type_location
                    )
                )) {
                    return false;
                }
            }

            return null;
        }

        if (!$declared_return_type->isMixed()) {
            if ($inferred_return_type->isVoid() && $declared_return_type->isVoid()) {
                return null;
            }

            if ($inferred_return_type->isMixed() || $inferred_return_type->isEmpty()) {
                if (IssueBuffer::accepts(
                    new MixedInferredReturnType(
                        'Could not verify return type \'' . $declared_return_type . '\' for ' .
                            $cased_method_id,
                        $return_type_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }

                return null;
            }

            if (!TypeChecker::isContainedBy(
                $codebase,
                $inferred_return_type,
                $declared_return_type,
                true,
                true,
                $has_scalar_match,
                $type_coerced,
                $type_coerced_from_mixed
            )) {
                // is the declared return type more specific than the inferred one?
                if ($type_coerced) {
                    if ($type_coerced_from_mixed) {
                        if (IssueBuffer::accepts(
                            new MixedTypeCoercion(
                                'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                                    ' is more specific than the inferred return type \'' . $inferred_return_type . '\'',
                                $return_type_location
                            ),
                            $suppressed_issues
                        )) {
                            return false;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new MoreSpecificReturnType(
                                'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                                    ' is more specific than the inferred return type \'' . $inferred_return_type . '\'',
                                $return_type_location
                            ),
                            $suppressed_issues
                        )) {
                            return false;
                        }
                    }
                } else {
                    if ($project_checker->alter_code
                        && isset($project_checker->getIssuesToFix()['InvalidReturnType'])
                    ) {
                        self::addOrUpdateReturnType(
                            $function,
                            $project_checker,
                            $inferred_return_type,
                            $source,
                            $function_like_checker,
                            ($project_checker->only_replace_php_types_with_non_docblock_types
                                || $unsafe_return_type)
                                && $inferred_return_type->from_docblock
                        );

                        return null;
                    }

                    if (IssueBuffer::accepts(
                        new InvalidReturnType(
                            'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                                ' is incorrect, got \'' . $inferred_return_type . '\'',
                            $return_type_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
            } elseif ($project_checker->alter_code
                    && isset($project_checker->getIssuesToFix()['LessSpecificReturnType'])
            ) {
                if (!TypeChecker::isContainedBy(
                    $codebase,
                    $declared_return_type,
                    $inferred_return_type,
                    false,
                    false
                )) {
                    self::addOrUpdateReturnType(
                        $function,
                        $project_checker,
                        $inferred_return_type,
                        $source,
                        $function_like_checker,
                        $compatible_method_ids
                        || (($project_checker->only_replace_php_types_with_non_docblock_types
                            || $unsafe_return_type)
                        && $inferred_return_type->from_docblock)
                    );

                    return null;
                }
            } elseif ((!$inferred_return_type->isNullable() && $declared_return_type->isNullable())
                || (!$inferred_return_type->isFalsable() && $declared_return_type->isFalsable())
            ) {
                if ($function instanceof Function_
                    || $function instanceof Closure
                    || $function->isPrivate()
                ) {
                    $check_for_less_specific_type = true;
                } elseif ($source instanceof StatementsChecker) {
                    $method_storage = $function_like_checker->getFunctionLikeStorage($source);

                    if ($method_storage instanceof MethodStorage) {
                        $check_for_less_specific_type = !$method_storage->overridden_somewhere;
                    } else {
                        $check_for_less_specific_type = false;
                    }
                } else {
                    $check_for_less_specific_type = false;
                }

                if ($check_for_less_specific_type) {
                    if (IssueBuffer::accepts(
                        new LessSpecificReturnType(
                            'The inferred return type \'' . $inferred_return_type . '\' for ' . $cased_method_id .
                                ' is more specific than the declared return type \'' . $declared_return_type . '\'',
                            $return_type_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
            }

            if (!$ignore_nullable_issues
                && $inferred_return_type->isNullable()
                && !$declared_return_type->isNullable()
                && !$declared_return_type->isVoid()
            ) {
                if ($project_checker->alter_code
                    && isset($project_checker->getIssuesToFix()['InvalidNullableReturnType'])
                    && !$inferred_return_type->isNull()
                ) {
                    self::addOrUpdateReturnType(
                        $function,
                        $project_checker,
                        $inferred_return_type,
                        $source,
                        $function_like_checker,
                        ($project_checker->only_replace_php_types_with_non_docblock_types
                            || $unsafe_return_type)
                            && $inferred_return_type->from_docblock
                    );

                    return null;
                }

                if (IssueBuffer::accepts(
                    new InvalidNullableReturnType(
                        'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                            ' is not nullable, but \'' . $inferred_return_type . '\' contains null',
                        $return_type_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }

            if (!$ignore_falsable_issues
                && $inferred_return_type->isFalsable()
                && !$declared_return_type->isFalsable()
                && !$declared_return_type->hasBool()
            ) {
                if ($project_checker->alter_code
                    && isset($project_checker->getIssuesToFix()['InvalidFalsableReturnType'])
                ) {
                    self::addOrUpdateReturnType(
                        $function,
                        $project_checker,
                        $inferred_return_type,
                        $source,
                        $function_like_checker,
                        ($project_checker->only_replace_php_types_with_non_docblock_types
                            || $unsafe_return_type)
                            && $inferred_return_type->from_docblock
                    );

                    return null;
                }

                if (IssueBuffer::accepts(
                    new InvalidFalsableReturnType(
                        'The declared return type \'' . $declared_return_type . '\' for ' . $cased_method_id .
                            ' does not allow false, but \'' . $inferred_return_type . '\' contains false',
                        $return_type_location
                    ),
                    $suppressed_issues
                )) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param Closure|Function_|ClassMethod $function
     *
     * @return false|null
     */
    public static function checkSignatureReturnType(
        FunctionLike $function,
        ProjectChecker $project_checker,
        FunctionLikeChecker $function_like_checker,
        FunctionLikeStorage $storage,
        Context $context
    ) {
        $codebase = $project_checker->codebase;

        if (!$storage->return_type || !$storage->return_type_location || $storage->has_template_return_type) {
            return;
        }

        if (!$storage->signature_return_type || $storage->signature_return_type === $storage->return_type) {
            $fleshed_out_return_type = ExpressionChecker::fleshOutType(
                $project_checker,
                $storage->return_type,
                $context->self,
                $context->self
            );

            $fleshed_out_return_type->check(
                $function_like_checker,
                $storage->return_type_location,
                $storage->suppressed_issues,
                [],
                false
            );

            return;
        }

        $fleshed_out_signature_type = ExpressionChecker::fleshOutType(
            $project_checker,
            $storage->signature_return_type,
            $context->self,
            $context->self
        );

        $fleshed_out_signature_type->check(
            $function_like_checker,
            $storage->signature_return_type_location ?: $storage->return_type_location,
            $storage->suppressed_issues,
            [],
            false
        );

        if ($function instanceof Closure) {
            return;
        }

        $fleshed_out_return_type = ExpressionChecker::fleshOutType(
            $project_checker,
            $storage->return_type,
            $context->self,
            $context->self
        );

        $fleshed_out_signature_type = ExpressionChecker::fleshOutType(
            $project_checker,
            $storage->signature_return_type,
            $context->self,
            $context->self
        );

        if (!TypeChecker::isContainedBy(
            $codebase,
            $fleshed_out_return_type,
            $fleshed_out_signature_type
        )
        ) {
            if ($project_checker->alter_code
                && isset($project_checker->getIssuesToFix()['MismatchingDocblockReturnType'])
            ) {
                self::addOrUpdateReturnType(
                    $function,
                    $project_checker,
                    $storage->signature_return_type,
                    $function_like_checker->getSource(),
                    $function_like_checker
                );

                return null;
            }

            if (IssueBuffer::accepts(
                new MismatchingDocblockReturnType(
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

    /**
     * @param Closure|Function_|ClassMethod $function
     * @param bool $docblock_only
     *
     * @return void
     */
    private static function addOrUpdateReturnType(
        FunctionLike $function,
        ProjectChecker $project_checker,
        Type\Union $inferred_return_type,
        StatementsSource $source,
        FunctionLikeChecker $function_like_checker,
        $docblock_only = false
    ) {
        $manipulator = FunctionDocblockManipulator::getForFunction(
            $project_checker,
            $source->getFilePath(),
            $function_like_checker->getMethodId(),
            $function
        );
        $manipulator->setReturnType(
            !$docblock_only && $project_checker->php_major_version >= 7
                ? $inferred_return_type->toPhpString(
                    $source->getNamespace(),
                    $source->getAliasedClassesFlipped(),
                    $source->getFQCLN(),
                    $project_checker->php_major_version,
                    $project_checker->php_minor_version
                ) : null,
            $inferred_return_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                false
            ),
            $inferred_return_type->toNamespacedString(
                $source->getNamespace(),
                $source->getAliasedClassesFlipped(),
                $source->getFQCLN(),
                true
            ),
            $inferred_return_type->canBeFullyExpressedInPhp()
        );
    }
}
