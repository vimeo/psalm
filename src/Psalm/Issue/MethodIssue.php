<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;

use function strtolower;

abstract class MethodIssue extends CodeIssue
{
    /**
     * @var string
     * @psalm-suppress PossiblyUnusedProperty Allows plugins to autoload the class.
     */
    public string $fq_class_name;
    /** @var lowercase-string */
    public $method_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $method_id
    ) {
        parent::__construct($message, $code_location);
        [$this->fq_class_name] = \explode('::', $method_id);
        $this->method_id = strtolower($method_id);
    }
}
