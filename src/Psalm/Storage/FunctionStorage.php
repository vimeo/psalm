<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * @api
 */
final class FunctionStorage extends FunctionLikeStorage
{
    use UnserializeMemoryUsageSuppressionTrait;
    /** @var array<string, bool> */
    public array $byref_uses = [];

    /**
     * How what the function returns starts, for the names of the keyed stores (see StringStartScanner)
     *
     * @internal
     */
    public ?string $return_string_start = null;
}
