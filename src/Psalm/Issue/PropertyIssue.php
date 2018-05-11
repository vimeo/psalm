<?php
namespace Psalm\Issue;

abstract class PropertyIssue extends CodeIssue
{
    /**
     * @var string
     */
    public $property_id;

    /**
     * @param string        $message
     * @param \Psalm\CodeLocation  $code_location
     * @param string        $property_id
     */
    public function __construct(
        $message,
        \Psalm\CodeLocation $code_location,
        $property_id
    ) {
        parent::__construct($message, $code_location);
        $this->property_id = $property_id;
    }
}
