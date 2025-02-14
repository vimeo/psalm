<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Override;
use Psalm\CodeLocation;

trait MixedIssueTrait
{
    /**
     * @readonly
     */
    public ?CodeLocation $origin_location = null;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        ?CodeLocation $origin_location = null,
    ) {
        parent::__construct($message, $code_location);
        $this->origin_location = $origin_location;
    }

    #[Override]
    public function getMixedOriginMessage(): string
    {
        return $this->message
            . ($this->origin_location
                ? '. Consider improving the type at ' . $this->origin_location->getShortSummary()
                : '');
    }

    #[Override]
    public function getOriginalLocation(): ?CodeLocation
    {
        return $this->origin_location;
    }
}
