<?php

namespace Psalm;

final class Aliases
{
    /**
     * @var array<lowercase-string, string>
     */
    public $uses;

    /**
     * @var array<lowercase-string, string>
     */
    public $uses_flipped;

    /**
     * @var array<lowercase-string, non-empty-string>
     */
    public $functions;

    /**
     * @var array<lowercase-string, string>
     */
    public $functions_flipped;

    /**
     * @var array<string, string>
     */
    public $constants;

    /**
     * @var array<string, string>
     */
    public $constants_flipped;

    /** @var string|null */
    public $namespace;

    /** @var ?int */
    public $namespace_first_stmt_start;

    /** @var ?int */
    public $uses_start;

    /** @var ?int */
    public $uses_end;

    /**
     * @param array<lowercase-string, string> $uses
     * @param array<lowercase-string, non-empty-string> $functions
     * @param array<string, string> $constants
     * @param array<lowercase-string, string> $uses_flipped
     * @param array<lowercase-string, string> $functions_flipped
     * @param array<string, string> $constants_flipped
     * @internal
     */
    public function __construct(
        ?string $namespace = null,
        array $uses = [],
        array $functions = [],
        array $constants = [],
        array $uses_flipped = [],
        array $functions_flipped = [],
        array $constants_flipped = []
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
