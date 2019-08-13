<?php

namespace Psalm\Internal\Taint;

use Psalm\CodeLocation;

class TypeSource
{
    /** @var string */
    public $id;

    /** @var ?CodeLocation */
    public $code_location;

    /** @var int */
    public $taint;

    public function __construct(string $id, ?CodeLocation $code_location, int $taint = 0)
    {
        $this->id = $id;
        $this->code_location = $code_location;
        $this->taint = $taint;
    }

    public static function getForMethodArgument(
        string $method_id,
        int $argument_offset,
        ?CodeLocation $code_location,
        ?CodeLocation $function_location = null
    ) : self {
        $function_id = $method_id . '#' . ($argument_offset + 1);

        if ($function_location) {
            $function_id .= '-' . $function_location->file_name . ':' . $function_location->raw_file_start;
        }

        return new self(\strtolower($function_id), $code_location);
    }

    public function __toString()
    {
        return $this->id;
    }
}
