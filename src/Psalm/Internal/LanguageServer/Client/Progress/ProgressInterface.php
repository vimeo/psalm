<?php

namespace Psalm\Internal\LanguageServer\Client\Progress;

/** @internal */
interface ProgressInterface
{
    public function begin(
        string $title,
        ?string $message = null,
        ?int $percentage = null
    ): void;

    public function update(?string $message = null, ?int $percentage = null): void;
    public function end(?string $message = null): void;
}
