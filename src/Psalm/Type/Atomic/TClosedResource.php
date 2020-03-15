<?php
namespace Psalm\Type\Atomic;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

class TClosedResource extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'closed-resource';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return 'closed-resource';
    }

    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        return 'closed-resource';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null|string
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return null;
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
