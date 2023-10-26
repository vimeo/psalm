<?php

declare(strict_types=1);

namespace Psalm;

final class Aliases
{
    /**
     * @var array<lowercase-string, string>
     */
    public array $uses;

    /**
     * @var array<lowercase-string, string>
     */
    public array $uses_flipped;

    /**
     * @var array<lowercase-string, non-empty-string>
     */
    public array $functions;

    /**
     * @var array<lowercase-string, string>
     */
    public array $functions_flipped;

    /**
     * @var array<string, string>
     */
    public array $constants;

    /**
     * @var array<string, string>
     */
    public array $constants_flipped;

    public ?string $namespace = null;

    public ?int $namespace_first_stmt_start = null;

    public ?int $uses_start = null;

    public ?int $uses_end = null;

    /**
     * @param array<lowercase-string, string> $uses
     * @param array<lowercase-string, non-empty-string> $functions
     * @param array<string, string> $constants
     * @param array<lowercase-string, string> $uses_flipped
     * @param array<lowercase-string, string> $functions_flipped
     * @param array<string, string> $constants_flipped
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        ?string $namespace = null,
        array $uses = [],
        array $functions = [],
        array $constants = [],
        array $uses_flipped = [],
        array $functions_flipped = [],
        array $constants_flipped = [],
    ) {
        $this->namespace = $namespace;
        $this->uses = $uses;
        $this->functions = $functions;
        $this->constants = $constants;
        $this->uses_flipped = $uses_flipped;
        $this->functions_flipped = $functions_flipped;
        $this->constants_flipped = $constants_flipped;
    }
}
