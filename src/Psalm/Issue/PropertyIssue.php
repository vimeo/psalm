<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\PropertyIdentifier;

/**
 * @api
 */
abstract class PropertyIssue extends CodeIssue
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public PropertyIdentifier $property_id,
    ) {
        parent::__construct($message, $code_location);
    }
}
