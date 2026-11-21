<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use function in_array;
use function is_string;
use function strtolower;

/**
 * A read-only view of the environment variables.
 *
 * @psalm-immutable
 * @internal
 */
final class Env
{
    /**
     * @param array<array-key, mixed> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    /**
     * The value of the first of the variables that is a non-empty string.
     */
    public function get(string ...$names): ?string
    {
        foreach ($names as $name) {
            if (isset($this->values[$name]) && is_string($this->values[$name]) && $this->values[$name] !== '') {
                return $this->values[$name];
            }
        }

        return null;
    }

    /**
     * Whether the variable is set to something but "0" or "false": the flag of a CI service is "true", "True" or "1".
     */
    public function isTruthy(string $name): bool
    {
        $value = $this->get($name);

        return $value !== null && !in_array(strtolower($value), ['0', 'false'], true);
    }

    /**
     * The repository of a variable that holds an "owner/name" slug.
     */
    public function repository(string $name): ?Repository
    {
        $slug = $this->get($name);

        return $slug === null ? null : Repository::fromSlug($slug);
    }

    /**
     * The variables of the names that are non-empty strings.
     *
     * @return array<string, string>
     */
    public function pick(string ...$names): array
    {
        $picked = [];

        foreach ($names as $name) {
            $value = $this->get($name);

            if ($value !== null) {
                $picked[$name] = $value;
            }
        }

        return $picked;
    }
}
