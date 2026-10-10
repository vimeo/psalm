<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use Override;
use Psalm\Progress\DefaultProgress;

final class CapturingDefaultProgress extends DefaultProgress
{
    public string $output = '';

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function write(string $message): void
    {
        $this->output .= $message;
    }
}
