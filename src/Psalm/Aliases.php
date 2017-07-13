<?php
namespace Psalm;

class Aliases
{
    /**
     * @var array<string, string>
     */
    public $uses;

    /**
     * @var array<string, string>
     */
    public $functions;

    /**
     * @var array<string, string>
     */
    public $constants;

    /** @var string|null */
    public $namespace;

    /**
     * @param string|null   $namespace
     * @param array         $uses
     * @param array         $functions
     * @param array         $constants
     */
    public function __construct(
        $namespace = null,
        array $uses = [],
        array $functions = [],
        array $constants = []
    ) {
        $this->namespace = $namespace;
        $this->uses = $uses;
        $this->functions = $functions;
        $this->constants = $constants;
    }
}
