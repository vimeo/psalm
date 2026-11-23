<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\TypeMismatch;

use Override;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\FalsableReturnStatement;
use Psalm\Issue\InvalidReturnStatement;
use Psalm\Issue\LessSpecificReturnStatement;
use Psalm\Issue\MixedReturnStatement;
use Psalm\Issue\MixedReturnTypeCoercion;
use Psalm\Issue\NullableReturnStatement;
use Psalm\Type\Union;

use function implode;

/**
 * Reports how the value of a return statement fits the declared return type
 *
 * @internal
 */
final class ReturnTypeMismatchReporter extends TypeMismatchReporter
{
    /**
     * @param Union $value_type the returned type, expanded
     * @param Union $returned_type the returned type as inferred
     * @psalm-capabilities read-props
     */
    public function __construct(
        StatementsAnalyzer $statements_analyzer,
        Union $value_type,
        Union $expected_type,
        CodeLocation $location,
        private readonly Union $returned_type,
        private readonly string $cased_method_id,
        private readonly bool $strict_types,
    ) {
        parent::__construct($statements_analyzer, $value_type, $expected_type, $location);
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function mixedCoercion(TypeComparisonResult $result): ?CodeIssue
    {
        if ($result->type_coerced_from_as_mixed) {
            return null;
        }

        if ($this->value_type->hasMixed()) {
            return new MixedReturnStatement('Could not infer a return type', $this->location);
        }

        return new MixedReturnTypeCoercion($this->getMoreGeneralMessage(), $this->location);
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function coercion(): ?CodeIssue
    {
        return new LessSpecificReturnStatement($this->getMoreGeneralMessage(), $this->location);
    }

    /**
     * ReturnTypeAnalyzer reports it once for the function-like
     *
     * @psalm-pure
     */
    #[Override]
    protected function toStringCast(): ?CodeIssue
    {
        return null;
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function mismatch(TypeComparisonResult $result, bool $possibly): ?CodeIssue
    {
        return new InvalidReturnStatement(
            'The inferred type \'' . $this->value_type->getId()
                . '\' does not match the declared return type \'' . $this->expected_type->getId()
                . '\' for ' . $this->cased_method_id
                . ($result->missing_shape_fields
                    ? ' due to additional array shape fields (' . implode(', ', $result->missing_shape_fields) . ')'
                    : ''),
            $this->location,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function possiblyNullIssue(): ?CodeIssue
    {
        return new NullableReturnStatement(
            'The declared return type \'' . $this->expected_type->getId() . '\' for ' . $this->cased_method_id
                . ' is not nullable, but the function returns \'' . $this->value_type->getId() . '\'',
            $this->location,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function possiblyFalseIssue(): ?CodeIssue
    {
        return new FalsableReturnStatement(
            'The declared return type \'' . $this->expected_type->getId() . '\' for ' . $this->cased_method_id
                . ' does not allow false, but the function returns \'' . $this->value_type->getId() . '\'',
            $this->location,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function isStrict(): bool
    {
        return $this->strict_types && !$this->expected_type->from_docblock;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function reportsPossiblyInvalid(): bool
    {
        return false;
    }

    #[Override]
    protected function acceptsNull(): bool
    {
        return parent::acceptsNull() || $this->expected_type->hasTemplate();
    }

    /**
     * @psalm-capabilities read-props
     */
    private function getMoreGeneralMessage(): string
    {
        return 'The type \'' . $this->returned_type->getId() . '\' is more general than the declared return type \''
            . $this->expected_type->getId() . '\' for ' . $this->cased_method_id;
    }
}
