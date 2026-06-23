<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Override;
use Psalm\CodeLocation;
use Psalm\Storage\FunctionLikeStorage;
use Stringable;

use function count;
use function strtolower;

/**
 * @psalm-consistent-constructor
 * @internal
 * @psalm-external-mutation-free
 */
final class DataFlowNode implements Stringable
{
    /**
     * @psalm-mutation-free
     */
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

    /**
     * @psalm-mutation-free
     */
    private function __clone()
    {
    }

    /**
     * @psalm-pure
     */
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

    /**
     * @psalm-pure
     */
    public static function getForMethodArgument(
        string $method_id,
        string $cased_method_id,
        int $argument_offset,
        ?FunctionLikeStorage $storage,
        ?CodeLocation $specialization_location = null,
        int $taints = 0,
    ): self {
        $arg_id = strtolower($method_id) . '#' . ($argument_offset + 1);

        $label = $cased_method_id . '#' . ($argument_offset + 1);

        $specialization_key = null;

        if ($specialization_location) {
            $specialization_key = strtolower($specialization_location->file_name)
                . ':' . $specialization_location->raw_file_start;
        }

        return self::make(
            $arg_id,
            $label,
            self::getParameterLocation($storage, $argument_offset) ?: ($storage ? null : $specialization_location),
            $specialization_key,
            $taints,
        );
    }

    /**
     * @psalm-pure
     */
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

    /**
     * @psalm-pure
     */
    public static function getForMethodReturn(
        string $method_id,
        string $cased_method_id,
        ?FunctionLikeStorage $storage,
        ?CodeLocation $specialization_location = null,
        int $taints = 0,
        ?string $specialization_key = null,
    ): self {
        if ($specialization_key === null && $specialization_location) {
            $specialization_key = strtolower($specialization_location->file_name)
                . ':' . $specialization_location->raw_file_start;
        }

        return self::make(
            strtolower($method_id),
            $cased_method_id,
            self::getReturnLocation($storage) ?: ($storage ? null : $specialization_location),
            $specialization_key,
            $taints,
        );
    }

    /**
     * @psalm-pure
     */
    private static function getReturnLocation(?FunctionLikeStorage $storage): ?CodeLocation
    {
        if (!$storage) {
            return null;
        }

        return $storage->signature_return_type_location
            ?: $storage->return_type_location
            ?: $storage->location;
    }

    /**
     * @psalm-pure
     */
    private static function getParameterLocation(?FunctionLikeStorage $storage, int $argument_offset): ?CodeLocation
    {
        if (!$storage) {
            return null;
        }

        $param = $storage->params[$argument_offset] ?? null;

        if (!$param && $storage->params) {
            $last_param = $storage->params[count($storage->params) - 1];
            $param = $last_param->is_variadic ? $last_param : null;
        }

        if (!$param) {
            return null;
        }

        return $param->signature_type_location
            ?: $param->type_location
            ?: $param->location;
    }


    private static self $forVariableUse;
    /**
     * @psalm-external-mutation-free
     */
    public static function getForVariableUse(): self
    {
        return self::$forVariableUse ??= new self('variable-use', null, null, 'variable use');
    }


    private static self $forUnknownOrigin;
    /**
     * @psalm-external-mutation-free
     */
    public static function getForUnknownOrigin(): self
    {
        return self::$forUnknownOrigin ??= new self('unknown-origin', null, null, 'unknown origin');
    }

    private static self $forClosureUse;
    /**
     * @psalm-external-mutation-free
     */
    public static function getForClosureUse(): self
    {
        return self::$forClosureUse ??= new self('closure-use', null, null, 'closure use');
    }

    /**
     * @psalm-mutation-free
     */
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

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function __toString(): string
    {
        return $this->id;
    }
}
