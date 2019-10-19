<?php

namespace Psalm\Internal\Taint;

use Psalm\CodeLocation;

abstract class Taintable
{
    /** @var string */
    public $id;

    /** @var string */
    public $label;

    /** @var ?CodeLocation */
    public $code_location;

    /** @var int */
    public $taint;

    public function __construct(string $id, string $label, ?CodeLocation $code_location, int $taint = 0)
    {
        $this->id = $id;
        $this->label = $label;
        $this->code_location = $code_location;
        $this->taint = $taint;
    }

    /**
     * @return static
     */
    public static function getForMethodArgument(
        string $method_id,
        string $cased_method_id,
        int $argument_offset,
        ?CodeLocation $code_location,
        ?CodeLocation $function_location = null
    ) {
        $function_id = $method_id . '#' . ($argument_offset + 1);

        $label = $cased_method_id . '#' . ($argument_offset + 1);

        if ($function_location) {
            $function_id .= '-' . $function_location->file_name . ':' . $function_location->raw_file_start;
        }

        return new static(\strtolower($function_id), $label, $code_location);
    }

    public function __toString()
    {
        return $this->id;
    }
}
