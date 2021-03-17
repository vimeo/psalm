<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;

class MixedAssignment extends CodeIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 32;

    /**
     * @var ?CodeLocation
     * @readonly
     */
    public $origin_location;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        ?CodeLocation $origin_location = null
    ) {
        $this->code_location = $code_location;
        $this->message = $message;
        $this->origin_location = $origin_location;
    }

    public function getMessage() : string
    {
        return $this->message
            . ($this->origin_location
                ? ', derived from expression at ' . $this->origin_location->getShortSummary()
                : '');
    }
}
