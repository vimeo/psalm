<?php

namespace Psalm\Internal\Taint;

use Psalm\CodeLocation;
use function strtolower;

abstract class Taintable
{
    /** @var string */
    public $id;

    /** @var ?string */
    public $unspecialized_id;

    /** @var string */
    public $label;

    /** @var ?CodeLocation */
    public $code_location;

    /** @var ?string */
    public $specialization_key;

    /** @var array<string> */
    public $taints;

    /** @var ?Taintable */
    public $previous;

    /**
     * @var array<string, array<string, true>>
     */
    public $specialized_calls = [];

    /**
     * @param array<string> $taints
     */
    public function __construct(
        string $id,
        string $label,
        ?CodeLocation $code_location,
        ?string $specialization_key = null,
        array $taints = []
    ) {
        $this->id = $id;

        if ($specialization_key) {
            $this->unspecialized_id = $id;
            $this->id .= '-' . $specialization_key;
        }

        $this->label = $label;
        $this->code_location = $code_location;
        $this->specialization_key = $specialization_key;
        $this->taints = $taints;
    }

    /**
     * @return static
     */
    final public static function getForMethodArgument(
        string $method_id,
        string $cased_method_id,
        int $argument_offset,
        ?CodeLocation $code_location,
        ?CodeLocation $function_location = null
    ) {
        $arg_id = $method_id . '#' . ($argument_offset + 1);

        $label = $cased_method_id . '#' . ($argument_offset + 1);

        $specialization_key = null;

        if ($function_location) {
            $specialization_key = strtolower($function_location->file_name) . ':' . $function_location->raw_file_start;
        }

        return new static(
            \strtolower($arg_id),
            $label,
            $code_location,
            $specialization_key
        );
    }

    /**
     * @return static
     */
    final public static function getForAssignment(
        string $var_id,
        CodeLocation $assignment_location
    ) {
        $id = $var_id . '-' . $assignment_location->file_name . ':' . $assignment_location->raw_file_start;

        return new static($id, $var_id, $assignment_location, null);
    }

    /**
     * @return static
     */
    final public static function getForMethodReturn(
        string $method_id,
        string $cased_method_id,
        ?CodeLocation $code_location,
        ?CodeLocation $function_location = null
    ) {
        $specialization_key = null;

        if ($function_location) {
            $specialization_key = strtolower($function_location->file_name) . ':' . $function_location->raw_file_start;
        }

        return new static(
            \strtolower($method_id),
            $cased_method_id,
            $code_location,
            $specialization_key
        );
    }

    public function __toString()
    {
        return $this->id;
    }
}
