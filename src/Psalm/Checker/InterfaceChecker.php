<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Context;

class InterfaceChecker extends ClassLikeChecker
{
    protected $parent_interfaces = [];

    protected static $existing_interfaces = [];
    protected static $existing_interfaces_ci = [];

    public function __construct(PhpParser\Node\Stmt\Interface_ $interface, StatementsSource $source, $absolute_class)
    {
        parent::__construct($interface, $source, $absolute_class);

        self::$existing_interfaces[$absolute_class] = true;
        self::$existing_interfaces_ci[strtolower($absolute_class)] = true;

        foreach ($interface->extends as $extended_interface) {
            $this->parent_interfaces[] = self::getAbsoluteClassFromName($extended_interface, $this->namespace, $this->aliased_classes);
        }
    }

    public static function interfaceExists($absolute_class)
    {
        if (isset(self::$existing_interfaces_ci[strtolower($absolute_class)])) {
            return self::$existing_interfaces_ci[strtolower($absolute_class)];
        }

        if (in_array($absolute_class, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (interface_exists($absolute_class, true)) {
            $reflected_interface = new \ReflectionClass($absolute_class);

            self::$existing_interfaces_ci[strtolower($absolute_class)] = true;
            self::$existing_interfaces[$reflected_interface->getName()] = true;
            return true;
        }

        self::$existing_interfaces_ci[strtolower($absolute_class)] = false;
        self::$existing_interfaces_ci[$absolute_class] = false;

        return false;
    }

    public static function hasCorrectCasing($absolute_class)
    {
        if (!self::interfaceExists($absolute_class)) {
            throw new \InvalidArgumentException('Cannot check casing on nonexistent class ' . $absolute_class);
        }

        return isset(self::$existing_interfaces[$absolute_class]);
    }
}
