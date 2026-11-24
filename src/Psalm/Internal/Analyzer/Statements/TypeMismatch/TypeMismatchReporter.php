<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\TypeMismatch;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\CodeIssue;
use Psalm\IssueBuffer;
use Psalm\Type\Union;

use function count;
use function reset;

/**
 * Reports how a value fits the type expected where it goes (an argument, a property, a returned value) once
 * UnionTypeComparator::isContainedBy() compared them. Each subclass gives the issues of its place.
 *
 * @internal
 */
abstract class TypeMismatchReporter
{
    public function __construct(
        protected readonly StatementsAnalyzer $statements_analyzer,
        protected readonly Union $value_type,
        protected readonly Union $expected_type,
        protected readonly CodeLocation $location,
    ) {
    }

    /**
     * Reports the coercion, __toString cast or mismatch found by the comparison
     *
     * @return bool whether the value fits the expected type, through a coercion or not
     */
    final public function reportComparison(bool $type_match_found, TypeComparisonResult $result): bool
    {
        if ($this->isStrict()) {
            // under strict_types, a native type casts neither a scalar nor an object
            $result->scalar_type_match_found = false;

            if ($result->to_string_cast) {
                // the object fits neither through the cast nor through a coercion of the cast string
                $result->to_string_cast = false;
                $result->type_coerced = false;
                $result->type_coerced_from_mixed = false;
                $type_match_found = false;
            }
        }

        if ($result->type_coerced) {
            $this->add($result->type_coerced_from_mixed ? $this->mixedCoercion($result) : $this->coercion());
        }

        if ($result->to_string_cast) {
            $this->add($this->toStringCast());
        }

        if ($type_match_found || $result->type_coerced) {
            return true;
        }

        $this->add($this->mismatch($result, $this->reportsPossiblyInvalid() && $this->canFit()));

        return false;
    }

    /**
     * Reports a null or a false value, or a possibly null or false one, the expected type does not accept
     *
     * @return bool whether the value is null or false, and the expected type does not accept it
     */
    final public function reportNullAndFalse(): bool
    {
        if (!$this->acceptsNull()) {
            if ($this->value_type->isNull()) {
                $this->add($this->nullIssue());

                return true;
            }

            if ($this->value_type->isNullable() && !$this->value_type->ignore_nullable_issues) {
                $this->add($this->possiblyNullIssue());
            }
        }

        if (!$this->acceptsFalse()) {
            if ($this->value_type->isFalse()) {
                $this->add($this->falseIssue());

                return true;
            }

            if ($this->value_type->isFalsable() && !$this->value_type->ignore_falsable_issues) {
                $this->add($this->possiblyFalseIssue());
            }
        }

        return false;
    }

    /**
     * Whether some of the values of the type can fit the expected type
     */
    final public function canFit(): bool
    {
        return UnionTypeComparator::canBeContainedBy(
            $this->statements_analyzer->getCodebase(),
            $this->value_type,
            $this->expected_type,
            true,
            true,
            coerced_parts_match: true,
        );
    }

    /**
     * The location the value comes from, when it comes from one other place
     */
    public static function getOriginLocation(
        StatementsAnalyzer $statements_analyzer,
        Union $type,
        CodeLocation $location,
    ): ?CodeLocation {
        $origin_locations = [];

        if ($statements_analyzer->variable_use_graph) {
            foreach ($type->parent_nodes as $parent_node) {
                $origin_locations = [
                    ...$origin_locations,
                    ...$statements_analyzer->variable_use_graph->getOriginLocations($parent_node),
                ];
            }
        }

        $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

        if ($origin_location && $origin_location->getHash() === $location->getHash()) {
            return null;
        }

        return $origin_location;
    }

    /**
     * The value is wider than the expected type only by a mixed part
     */
    abstract protected function mixedCoercion(TypeComparisonResult $result): ?CodeIssue;

    /**
     * The value is wider than the expected type
     */
    abstract protected function coercion(): ?CodeIssue;

    /**
     * The value is an object that fits the expected type once cast to string
     */
    abstract protected function toStringCast(): ?CodeIssue;

    /**
     * The value does not fit the expected type
     *
     * @param bool $possibly some of the values of the type can fit, when reportsPossiblyInvalid()
     */
    abstract protected function mismatch(TypeComparisonResult $result, bool $possibly): ?CodeIssue;

    abstract protected function possiblyNullIssue(): ?CodeIssue;

    abstract protected function possiblyFalseIssue(): ?CodeIssue;

    protected function nullIssue(): ?CodeIssue
    {
        return $this->possiblyNullIssue();
    }

    protected function falseIssue(): ?CodeIssue
    {
        return $this->possiblyFalseIssue();
    }

    /**
     * Whether the value is checked under strict_types, where only an int fits a float
     */
    protected function isStrict(): bool
    {
        return false;
    }

    protected function reportsPossiblyInvalid(): bool
    {
        return true;
    }

    protected function acceptsNull(): bool
    {
        return $this->expected_type->isNullable();
    }

    protected function acceptsFalse(): bool
    {
        return $this->expected_type->isFalsable()
            || isset($this->expected_type->getAtomicTypes()['bool'])
            || $this->expected_type->hasScalar();
    }

    final protected function add(?CodeIssue $issue): void
    {
        if ($issue) {
            IssueBuffer::maybeAdd($issue, $this->statements_analyzer->getSuppressedIssues());
        }
    }
}
