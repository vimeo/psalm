<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\TypeMismatch;

use Override;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\Issue\NullArgument;
use Psalm\Issue\PossiblyFalseArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function implode;

/**
 * @internal
 */
final class ArgumentTypeMismatchReporter extends TypeMismatchReporter
{
    private readonly string $argument;

    /** echo and print take any scalar, which they cast to string */
    private readonly bool $casts_to_string;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(
        StatementsAnalyzer $statements_analyzer,
        Union $value_type,
        Union $expected_type,
        CodeLocation $location,
        int $argument_offset,
        private readonly ?string $cased_method_id,
        private readonly bool $strict_types,
    ) {
        parent::__construct($statements_analyzer, $value_type, $expected_type, $location);

        $this->argument = 'Argument ' . ($argument_offset + 1)
            . ($cased_method_id !== null && $cased_method_id !== '' ? ' of ' . $cased_method_id : '');
        $this->casts_to_string = $cased_method_id === 'echo' || $cased_method_id === 'print';
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function isStrict(): bool
    {
        return $this->strict_types
            && !$this->value_type->hasArray()
            && !$this->expected_type->from_docblock
            && !$this->casts_to_string
            && $this->cased_method_id !== 'sprintf';
    }

    #[Override]
    protected function mixedCoercion(TypeComparisonResult $result): ?CodeIssue
    {
        if ($this->value_type->hasMixed()) {
            return null;
        }

        return new MixedArgumentTypeCoercion(
            $this->argument . ' expects ' . $this->expected_type->getId()
                . ', but parent type ' . $this->value_type->getId() . ' provided',
            $this->location,
            $this->cased_method_id,
            self::getOriginLocation($this->statements_analyzer, $this->value_type, $this->location),
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function coercion(): ?CodeIssue
    {
        if ($this->value_type->hasMixed() || $this->casts_to_string) {
            return null;
        }

        return new ArgumentTypeCoercion(
            $this->argument . ' expects ' . $this->expected_type->getId()
                . ', but parent type ' . $this->value_type->getId() . ' provided',
            $this->location,
            $this->cased_method_id,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function toStringCast(): ?CodeIssue
    {
        if ($this->casts_to_string) {
            return null;
        }

        return new ImplicitToStringCast(
            $this->argument . ' expects ' . $this->expected_type->getId()
                . ', but ' . $this->value_type->getId() . ' provided with a __toString method',
            $this->location,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function mismatch(TypeComparisonResult $result, bool $possibly): ?CodeIssue
    {
        $type = ($this->value_type->possibly_undefined ? 'possibly undefined ' : '') . $this->value_type->getId();

        if ($result->scalar_type_match_found) {
            if ($this->casts_to_string) {
                return null;
            }

            return new InvalidScalarArgument(
                $this->argument . ' expects ' . $this->expected_type->getId() . ', but ' . $type . ' provided',
                $this->location,
                $this->cased_method_id,
            );
        }

        if ($possibly) {
            return new PossiblyInvalidArgument(
                $this->argument . ' expects ' . $this->expected_type->getId()
                    . ', but possibly different type ' . $type . ' provided',
                $this->location,
                $this->cased_method_id,
            );
        }

        return new InvalidArgument(
            $this->argument . ' expects ' . $this->expected_type->getId() . ', but ' . $type
                . ($result->missing_shape_fields
                    ? ' with additional array shape fields (' . implode(', ', $result->missing_shape_fields) . ') was'
                    : '')
                . ' provided',
            $this->location,
            $this->cased_method_id,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function nullIssue(): ?CodeIssue
    {
        return new NullArgument(
            $this->argument . ' cannot be null, null value provided to parameter with type '
                . $this->expected_type->getId(),
            $this->location,
            $this->cased_method_id,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function possiblyNullIssue(): ?CodeIssue
    {
        return new PossiblyNullArgument(
            $this->argument . ' cannot be null, possibly null value provided',
            $this->location,
            $this->cased_method_id,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function falseIssue(): ?CodeIssue
    {
        return new InvalidArgument(
            $this->argument . ' cannot be false, ' . $this->expected_type->getId() . ' value expected',
            $this->location,
            $this->cased_method_id,
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    protected function possiblyFalseIssue(): ?CodeIssue
    {
        return new PossiblyFalseArgument(
            $this->argument . ' cannot be false, possibly ' . $this->expected_type->getId() . ' value expected',
            $this->location,
            $this->cased_method_id,
        );
    }

    #[Override]
    protected function acceptsNull(): bool
    {
        return $this->casts_to_string || parent::acceptsNull() || $this->expectsTypeVariable();
    }

    #[Override]
    protected function acceptsFalse(): bool
    {
        return $this->casts_to_string || parent::acceptsFalse() || $this->expectsTypeVariable();
    }

    /**
     * A null or false argument against a type variable records a bound like any other value
     *
     * @psalm-capabilities read-props
     */
    private function expectsTypeVariable(): bool
    {
        foreach ($this->expected_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTypeVariable) {
                return true;
            }
        }

        return false;
    }
}
