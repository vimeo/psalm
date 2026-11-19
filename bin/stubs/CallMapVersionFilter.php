<?php

declare(strict_types=1);

namespace Psalm\Internal\Stubs;

use function explode;

/**
 * Removes extension polyfills of APIs whose availability is controlled by PHP itself.
 *
 * @internal
 */
final class CallMapVersionFilter
{
    /** @param array<string, array<string, true>> $bundledOwners */
    public function __construct(private readonly array $bundledOwners)
    {
    }

    /**
     * @param array<string, array<int|string, string>> $map
     * @param array<string, string> $extensions
     * @return array<string, array<int|string, string>>
     * @psalm-capabilities read-props
     */
    public function filter(array $map, array $extensions): array
    {
        foreach ($map as $function => $_) {
            $canonical = explode("'", $function, 2)[0];
            if (isset($this->bundledOwners[$canonical])
                && !isset($this->bundledOwners[$canonical][$extensions[$canonical] ?? ''])
            ) {
                unset($map[$function]);
            }
        }

        return $map;
    }
}
