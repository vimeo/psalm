<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use AssertionError;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;
use UnexpectedValueException;

use function count;
use function preg_match;
use function reset;
use function strlen;
use function strpbrk;

/**
 * @internal
 */
final class ConcatAnalyzer
{
    private const MAX_LITERALS = 64;

    /**
     * The taints a value can't have once appended to $prefix, if $prefix is the start of a URL fixing its origin: a
     * network scheme or `//`, a host and the `/`, `?` or `#` ending it. What follows such a prefix can only change the
     * path, the query or the fragment of the URL, never the server it is sent to (`ssrf` taint). With a network scheme
     * it can't make the URL a local file either (`file` taint), unlike with no scheme. After any other scheme, a
     * stream wrapper (`php://filter/resource=`, `compress.zlib://https://`...), it can still be the URL of any server.
     * In the query or the fragment, it can't be a `..` segment of the path either (`url_path` taint).
     *
     * A path from the root (`/` followed by anything but another `/` or a backslash) is resolved against the server of
     * the base URL it is sent with, or is a local file: what follows it can't choose a server either.
     *
     * @psalm-pure
     */
    public static function getTaintsRemovedAfterUrlOrigin(string $prefix): int
    {
        if (preg_match('~^/[^/\\\\]~', $prefix) === 1) {
            return TaintKind::INPUT_SSRF;
        }

        if (preg_match('~^(?:(https?|ftps?):)?//[^/?#]+([/?#].*)~is', $prefix, $matches) !== 1) {
            return 0;
        }

        $removed_taints = ($matches[1] ?? '') === ''
            ? TaintKind::INPUT_SSRF
            : TaintKind::INPUT_SSRF | TaintKind::INPUT_FILE;

        // from the `/`, `?` or `#` ending the host
        return strpbrk($matches[2] ?? '', '?#') === false
            ? $removed_taints
            : $removed_taints | TaintKind::INPUT_URL_PATH;
    }

    /**
     * The taints a value can't have once appended to any of $prefixes (see getTaintsRemovedAfterUrlOrigin()): only
     * those every one of them removes
     *
     * @param list<string> $prefixes
     * @psalm-pure
     */
    public static function getTaintsRemovedAfterUrlOrigins(array $prefixes): int
    {
        $removed_taints = null;

        foreach ($prefixes as $prefix) {
            $removed_taints = $removed_taints === null
                ? self::getTaintsRemovedAfterUrlOrigin($prefix)
                : $removed_taints & self::getTaintsRemovedAfterUrlOrigin($prefix);
        }

        return $removed_taints ?? 0;
    }

    /**
     * The literal strings $expr, already analyzed, can start with as far as they are known: none if it can start with
     * anything
     *
     * @return list<string>
     * @psalm-capabilities read-props
     */
    public static function getLiteralPrefixes(StatementsAnalyzer $statements_analyzer, PhpParser\Node\Expr $expr): array
    {
        $type = $statements_analyzer->node_data->getType($expr);

        if ($type && $type->allStringLiterals()) {
            return self::getLiteralValues($type);
        }

        // the start of a concatenation or of an interpolated string, recorded when it was analyzed
        return $statements_analyzer->node_data->getLiteralPrefixes($expr) ?? [];
    }

    /**
     * The literal strings $left . $right, both already analyzed, can start with (see getLiteralPrefixes())
     *
     * @return list<string>
     * @psalm-capabilities read-props
     */
    public static function getConcatLiteralPrefixes(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
    ): array {
        $left_type = $statements_analyzer->node_data->getType($left);

        if (!$left_type || !$left_type->allStringLiterals()) {
            return self::getLiteralPrefixes($statements_analyzer, $left);
        }

        $left_values = self::getLiteralValues($left_type);

        // all of the left side is known: what the right side starts with follows it
        return self::concatLiterals($left_values, self::getLiteralPrefixes($statements_analyzer, $right) ?: [''])
            ?? $left_values;
    }

    /**
     * Each of $prefixes followed by each of $suffixes, null if that's too many strings
     *
     * @param list<string> $prefixes
     * @param list<string> $suffixes
     * @return list<string>|null
     * @psalm-pure
     */
    public static function concatLiterals(array $prefixes, array $suffixes): ?array
    {
        if (count($prefixes) * count($suffixes) > self::MAX_LITERALS) {
            return null;
        }

        $strings = [];

        foreach ($prefixes as $prefix) {
            foreach ($suffixes as $suffix) {
                $strings[] = $prefix . $suffix;
            }
        }

        return $strings;
    }

    /**
     * @return list<string>
     * @psalm-mutation-free
     */
    public static function getLiteralValues(Union $type): array
    {
        $values = [];

        foreach ($type->getLiteralStrings() as $literal) {
            $values[] = $literal->value;
        }

        return $values;
    }

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        ?Union &$result_type = null,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $left_type = $statements_analyzer->node_data->getType($left);
        $right_type = $statements_analyzer->node_data->getType($right);
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->hasMixed() || $right_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if ($left_type->hasMixed()) {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $left);

                    $origin_locations = [];

                    if ($statements_analyzer->variable_use_graph) {
                        foreach ($left_type->parent_nodes as $parent_node) {
                            $origin_locations = [
                                ...$origin_locations,
                                ...$statements_analyzer->variable_use_graph->getOriginLocations($parent_node),
                            ];
                        }
                    }

                    $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                    if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                        $origin_location = null;
                    }

                    IssueBuffer::maybeAdd(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            $arg_location,
                            $origin_location,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $right);
                    $origin_locations = [];

                    if ($statements_analyzer->variable_use_graph) {
                        foreach ($right_type->parent_nodes as $parent_node) {
                            $origin_locations = [
                                ...$origin_locations,
                                ...$statements_analyzer->variable_use_graph->getOriginLocations($parent_node),
                            ];
                        }
                    }

                    $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                    if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                        $origin_location = null;
                    }

                    IssueBuffer::maybeAdd(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            $arg_location,
                            $origin_location,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                return;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            self::analyzeOperand($statements_analyzer, $left, $left_type, 'Left', $context);
            self::analyzeOperand($statements_analyzer, $right, $right_type, 'Right', $context);

            // If both types are specific literals, combine them into new literals
            $literal_concat = false;

            if ($left_type->allSpecificLiterals() && $right_type->allSpecificLiterals()) {
                $left_type_parts = $left_type->getAtomicTypes();
                $right_type_parts = $right_type->getAtomicTypes();
                $combinations = count($left_type_parts) * count($right_type_parts);
                if ($combinations < self::MAX_LITERALS) {
                    $literal_concat = true;
                    $result_type_parts = [];

                    foreach ($left_type->getAtomicTypes() as $left_type_part) {
                        foreach ($right_type->getAtomicTypes() as $right_type_part) {
                            $literal = $left_type_part->value . $right_type_part->value;
                            if (strlen($literal) >= $config->max_string_length) {
                                // Literal too long, use non-literal type instead
                                $literal_concat = false;
                                break 2;
                            }

                            $result_type_parts[] = Type::getAtomicStringFromLiteral($literal);
                        }
                    }

                    if ($literal_concat) {
                        if (count($result_type_parts) === 0) {
                            throw new AssertionError("The number of parts cannot be 0!");
                        }
                        if (count($result_type_parts) !== $combinations) {
                            throw new AssertionError("The number of parts does not match!");
                        }
                        $result_type = new Union($result_type_parts);
                    }
                }
            }

            if (!$literal_concat) {
                $numeric_type = new Union([
                    new TNumericString,
                    new TInt,
                    new TFloat,
                ]);
                $left_is_numeric = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $numeric_type,
                );

                $right_is_numeric = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $numeric_type,
                );

                $has_numeric_type = $left_is_numeric || $right_is_numeric;

                if ($left_is_numeric) {
                    $right_uint = Type::getListKey();
                    $right_is_uint = UnionTypeComparator::isContainedBy(
                        $codebase,
                        $right_type,
                        $right_uint,
                    );

                    if ($right_is_uint) {
                        $result_type = Type::getNumericString();
                        return;
                    }
                }

                $lowercase_type = $numeric_type->getBuilder()->addType(new TLowercaseString())->freeze();

                $all_lowercase = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $lowercase_type,
                ) && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $lowercase_type,
                );

                $non_empty_string = $numeric_type->getBuilder()->addType(new TNonEmptyString())->freeze();

                $left_non_empty = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $non_empty_string,
                );

                $right_non_empty = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $non_empty_string,
                );

                $has_non_empty = $left_non_empty || $right_non_empty;
                $all_non_empty = $left_non_empty && $right_non_empty;

                $has_numeric_and_non_empty = $has_numeric_type && $has_non_empty;

                $non_falsy_string = $numeric_type->getBuilder()->addType(new TNonFalsyString())->freeze();
                $left_non_falsy = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $non_falsy_string,
                );

                $right_non_falsy = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $non_falsy_string,
                );

                $all_literals = $left_type->allLiterals() && $right_type->allLiterals();

                if ($has_non_empty) {
                    if ($all_literals) {
                        $result_type = new Union([new TNonEmptyNonspecificLiteralString]);
                    } elseif ($all_lowercase) {
                        $result_type = Type::getNonEmptyLowercaseString();
                    } elseif ($all_non_empty || $has_numeric_and_non_empty || $left_non_falsy || $right_non_falsy) {
                        $result_type = Type::getNonFalsyString();
                    } else {
                        $result_type = Type::getNonEmptyString();
                    }
                } else {
                    if ($all_literals) {
                        $result_type = new Union([new TNonspecificLiteralString]);
                    } elseif ($all_lowercase) {
                        $result_type = Type::getLowercaseString();
                    } else {
                        $result_type = Type::getString();
                    }
                }
            }
        } elseif ($left_type || $right_type) {
            /**
             * @var Union $known_operand
             */
            $known_operand = $right_type ?? $left_type;

            if ($known_operand->isSingle()) {
                $known_operands_atomic = $known_operand->getSingleAtomic();

                if ($known_operand->isNonEmptyString()) {
                    $result_type = Type::getNonEmptyString();
                }

                if ($known_operands_atomic instanceof TNonFalsyString) {
                    $result_type = Type::getNonFalsyString();
                }

                if ($known_operands_atomic instanceof TLiteralString) {
                    if ($known_operands_atomic->value) {
                        $result_type = Type::getNonFalsyString();
                    } elseif ($known_operands_atomic->value !== '') {
                        $result_type = Type::getNonEmptyString();
                    }
                }
            }
        }
    }

    private static function analyzeOperand(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $operand,
        Union $operand_type,
        string $side,
        Context $context,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $config = Config::getInstance();

        if ($operand_type->isNull()) {
            IssueBuffer::maybeAdd(
                new NullOperand(
                    'Cannot concatenate with a ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        if ($operand_type->isFalse()) {
            IssueBuffer::maybeAdd(
                new FalseOperand(
                    'Cannot concatenate with a ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        if ($operand_type->isNullable() && !$operand_type->ignore_nullable_issues) {
            IssueBuffer::maybeAdd(
                new PossiblyNullOperand(
                    'Cannot concatenate with a possibly null ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($operand_type->isFalsable() && !$operand_type->ignore_falsable_issues) {
            IssueBuffer::maybeAdd(
                new PossiblyFalseOperand(
                    'Cannot concatenate with a possibly false ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $operand_type_match = true;
        $has_valid_operand = false;
        $comparison_result = new TypeComparisonResult();

        foreach ($operand_type->getAtomicTypes() as $operand_type_part) {
            if ($operand_type_part instanceof TTypeVariable) {
                // a type variable concatenates, constrained from above to array-key
                $statements_analyzer->type_variable_tracker->addBounds(
                    [],
                    [[$operand_type_part->name, new TemplateBound(Type::getArrayKey())]],
                    new CodeLocation($statements_analyzer->getSource(), $operand),
                );

                $has_valid_operand = true;

                continue;
            }

            if ($operand_type_part instanceof TTemplateParam && !$operand_type_part->as->isString()) {
                IssueBuffer::maybeAdd(
                    new MixedOperand(
                        "$side operand cannot be a non-string template param",
                        new CodeLocation($statements_analyzer->getSource(), $operand),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return;
            }

            if ($operand_type_part instanceof TNull || $operand_type_part instanceof TFalse) {
                continue;
            }

            $operand_type_part_match = AtomicTypeComparator::isContainedBy(
                $codebase,
                $operand_type_part,
                new TString,
                false,
                false,
                $comparison_result,
            );

            $operand_type_match = $operand_type_match && $operand_type_part_match;

            $has_valid_operand = $has_valid_operand || $operand_type_part_match;

            if ($comparison_result->to_string_cast && $config->strict_binary_operands) {
                IssueBuffer::maybeAdd(
                    new ImplicitToStringCast(
                        "$side side of concat op expects string, '$operand_type' provided with a __toString method",
                        new CodeLocation($statements_analyzer->getSource(), $operand),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            foreach ($operand_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    $to_string_method_id = new MethodIdentifier(
                        $atomic_type->value,
                        '__tostring',
                    );

                    if ($codebase->methodExists(
                        $to_string_method_id,
                        $context->calling_method_id,
                        $codebase->collect_locations
                            ? new CodeLocation($statements_analyzer->getSource(), $operand)
                            : null,
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $statements_analyzer
                            : null,
                        $statements_analyzer->getFilePath(),
                    )) {
                        try {
                            $storage = $codebase->methods->getStorage($to_string_method_id);
                        } catch (UnexpectedValueException) {
                            continue;
                        }

                        $var_id = ExpressionIdentifier::getExtendedVarId(
                            $operand,
                            $statements_analyzer->getFQCLN(),
                            $statements_analyzer,
                        );
                        $statements_analyzer->signalMutation(
                            $storage->capabilities,
                            $context,
                            'possibly-mutating method '
                                        . $atomic_type->value . '::__toString',
                            ImpureMethodCall::class,
                            $operand,
                            null,
                            false,
                            $var_id === '$this' ? $storage : null,
                        );
                    }
                }
            }
        }

        if (!$operand_type_match
            && (!$comparison_result->scalar_type_match_found
                || (!$operand_type->isConcatSafe() && $config->strict_binary_operands)
            )
        ) {
            if ($has_valid_operand) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidOperand(
                        'Cannot concatenate with a ' . $operand_type,
                        new CodeLocation($statements_analyzer->getSource(), $operand),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidOperand(
                        'Cannot concatenate with a ' . $operand_type,
                        new CodeLocation($statements_analyzer->getSource(), $operand),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }
    }
}
