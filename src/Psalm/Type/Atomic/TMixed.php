<?php
namespace Psalm\Type\Atomic;

class TMixed extends \Psalm\Type\Atomic
{
    /** @var bool */
    public $from_loop_isset = false;

    /**
     * @param bool $from_loop_isset
     */
    public function __construct($from_loop_isset = false)
    {
        $this->from_loop_isset = $from_loop_isset;
    }

    public function __toString(): string
    {
        return 'mixed';
    }
    
    public function getKey(bool $include_extra = true): string
    {
        return 'mixed';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
