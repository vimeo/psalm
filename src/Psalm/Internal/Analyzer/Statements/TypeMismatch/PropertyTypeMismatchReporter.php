<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\TypeMismatch;

use Override;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidPropertyAssignmentValue;
use Psalm\Issue\MixedPropertyTypeCoercion;
use Psalm\Issue\PossiblyFalsePropertyAssignmentValue;
use Psalm\Issue\PossiblyInvalidPropertyAssignmentValue;
use Psalm\Issue\PossiblyNullPropertyAssignmentValue;
use Psalm\Issue\PropertyTypeCoercion;
use Psalm\Type\Union;

/**
 * @internal
 */
final class PropertyTypeMismatchReporter extends TypeMismatchReporter
{
    /**
     * @param string $name the property as written at the assignment
     * @param bool $defers_mismatch a mismatch is reported by reportDeferredMismatch(), once the other properties the
     *                              assignment may write are checked
     */
    public function __construct(
        StatementsAnalyzer $statements_analyzer,
        Union $value_type,
        Union $expected_type,
        CodeLocation $location,
        private readonly string $name,
        private readonly string $property_id,
        private readonly bool $strict_types,
        private readonly bool $defers_mismatch = false,
    ) {
        parent::__construct($statements_analyzer, $value_type, $expected_type, $location);
    }

    public function reportDeferredMismatch(bool $possibly): void
    {
        $this->add($this->createMismatch($possibly));
    }

    #[Override]
    protected function mixedCoercion(TypeComparisonResult $result): ?CodeIssue
    {
        return new MixedPropertyTypeCoercion(
            $this->name . ' expects \'' . $this->expected_type->getId() . '\', '
                . ' parent type `' . $this->value_type->getId() . '` provided',
            $this->location,
            $this->property_id,
        );
    }

    #[Override]
    protected function coercion(): ?CodeIssue
    {
        return new PropertyTypeCoercion(
            $this->name . ' expects \'' . $this->expected_type->getId() . '\', '
                . ' parent type \'' . $this->value_type->getId() . '\' provided',
            $this->location,
            $this->property_id,
        );
    }

    #[Override]
    protected function toStringCast(): ?CodeIssue
    {
        return new ImplicitToStringCast(
            $this->name . ' expects \'' . $this->expected_type . '\', '
                . '\'' . $this->value_type . '\' provided with a __toString method',
            $this->location,
        );
    }

    #[Override]
    protected function mismatch(TypeComparisonResult $result, bool $possibly): ?CodeIssue
    {
        return $this->defers_mismatch ? null : $this->createMismatch($possibly);
    }

    #[Override]
    protected function possiblyNullIssue(): ?CodeIssue
    {
        return new PossiblyNullPropertyAssignmentValue(
            $this->name . ' with non-nullable declared type \'' . $this->expected_type
                . '\' cannot be assigned nullable type \'' . $this->value_type . '\'',
            $this->location,
            $this->property_id,
        );
    }

    #[Override]
    protected function possiblyFalseIssue(): ?CodeIssue
    {
        return new PossiblyFalsePropertyAssignmentValue(
            $this->name . ' with non-falsable declared type \'' . $this->expected_type
                . '\' cannot be assigned possibly false type \'' . $this->value_type . '\'',
            $this->location,
            $this->property_id,
        );
    }

    #[Override]
    protected function isStrict(): bool
    {
        return $this->strict_types && !$this->expected_type->from_docblock;
    }

    #[Override]
    protected function reportsPossiblyInvalid(): bool
    {
        return !$this->defers_mismatch;
    }

    private function createMismatch(bool $possibly): CodeIssue
    {
        if ($possibly) {
            return new PossiblyInvalidPropertyAssignmentValue(
                $this->name . ' with declared type \'' . $this->expected_type->getId()
                    . '\' cannot be assigned possibly different type \'' . $this->value_type->getId() . '\'',
                $this->location,
                $this->property_id,
            );
        }

        return new InvalidPropertyAssignmentValue(
            $this->name . ' with declared type \'' . $this->expected_type->getId()
                . '\' cannot be assigned type \'' . $this->value_type->getId() . '\'',
            $this->location,
            $this->property_id,
        );
    }
}
