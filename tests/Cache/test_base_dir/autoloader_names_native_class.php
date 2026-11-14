<?php

declare(strict_types=1);

// like the autoload files of a Composer project, this one names a native class (the Normalizer of intl, say)
return /** @psalm-pure */ static function (): void {
    throw new JsonException('names a native class');
};
