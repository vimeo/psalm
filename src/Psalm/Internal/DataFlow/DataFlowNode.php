<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Override;
use Psalm\CodeLocation;
use Stringable;

use function strtolower;

/**
 * @psalm-consistent-constructor
 * @internal
 */
final class DataFlowNode implements Stringable
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $unspecialized_id,
        public readonly ?string $specialization_key,
        public readonly string $label,
        public readonly ?CodeLocation $code_location = null,
        public readonly int $taints = 0,
        public readonly ?self $taintSource = null,
        /** @var list<string> */
        public readonly array $path_types = [],
        /**
         * @var array<string, array<string, string>>
         */
        public readonly array $specialized_calls = [],
    ) {
    }

    private function __clone()
    {
    }

    public static function make(
        string $id,
        string $label,
        ?CodeLocation $code_location,
        ?string $specialization_key = null,
        int $taints = 0,
    ): self {
        if ($specialization_key === null) {
            $unspecialized_id = null;
        } else {
            $unspecialized_id = $id;
            $id .= ' specialized in ' . $specialization_key;
        }
        return new self(
            $id,
            $unspecialized_id,
            $specialization_key,
            $label,
            $code_location,
            $taints,
        );
    }

    public static function getForMethodArgument(
        string $method_id,
        string $cased_method_id,
        int $argument_offset,
        ?CodeLocation $arg_location,
        ?CodeLocation $code_location = null,
        int $taints = 0,
    ): self {
        $arg_id = strtolower($method_id) . '#' . ($argument_offset + 1);

        $label = $cased_method_id . '#' . ($argument_offset + 1);

        $specialization_key = null;

        if ($code_location) {
            $specialization_key = strtolower($code_location->file_name) . ':' . $code_location->raw_file_start;
        }

        return self::make(
            $arg_id,
            $label,
            $arg_location,
            $specialization_key,
            $taints,
        );
    }

    public static function getForAssignment(
        string $var_id,
        CodeLocation $assignment_location,
        ?string $specialization_key = null,
    ): self {
        $label = $var_id;
        $var_id .= ' from ' . strtolower($assignment_location->file_name)
            . ':' . $assignment_location->raw_file_start
            . '-' . $assignment_location->raw_file_end;

        return self::make($var_id, $label, $assignment_location, $specialization_key);
    }
    
    public static function getForMethodReturn(
        string $method_id,
        string $cased_method_id,
        ?CodeLocation $code_location,
        ?CodeLocation $function_location = null,
        int $taints = 0,
    ): self {
        $specialization_key = null;

        if ($function_location) {
            $specialization_key = strtolower($function_location->file_name) . ':' . $function_location->raw_file_start;
        }

        return self::make(
            strtolower($method_id),
            $cased_method_id,
            $code_location,
            $specialization_key,
            $taints,
        );
    }


    private static self $forVariableUse;
    public static function getForVariableUse(): self
    {
        return self::$forVariableUse ??= new self('variable-use', null, null, 'variable use');
    }


    private static self $forUnknownOrigin;
    public static function getForUnknownOrigin(): self
    {
        return self::$forUnknownOrigin ??= new self('unknown-origin', null, null, 'unknown origin');
    }

    private static self $forClosureUse;
    public static function getForClosureUse(): self
    {
        return self::$forClosureUse ??= new self('closure-use', null, null, 'closure use');
    }

    public function setTaints(int $taints): self
    {
        if ($this->taints === $taints) {
            return $this;
        }
        return new self(
            $this->id,
            $this->unspecialized_id,
            $this->specialization_key,
            $this->label,
            $this->code_location,
            $taints,
            $this->taintSource,
            $this->path_types,
            $this->specialized_calls,
        );
    }

    #[Override]
    public function __toString(): string
    {
        return $this->id;
    }
}
