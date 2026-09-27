<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

/**
 * @api
 */
interface MixedIssue
{
    public function getMixedOriginMessage(): string;

    public function getOriginalLocation(): ?CodeLocation;
}
