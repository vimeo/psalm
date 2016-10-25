<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Context;

class InterfaceChecker extends ClassLikeChecker
{
    /**
     * @var array<string,array<string>>
     */
    protected static $parent_interfaces = [];

    protected static $existing_interfaces = [];
    protected static $existing_interfaces_ci = [];

    /**
     * @param PhpParser\Node\Stmt\Interface_ $interface
     * @param StatementsSource               $source
     * @param string                         $interface_name
     */
    public function __construct(PhpParser\Node\Stmt\Interface_ $interface, StatementsSource $source, $interface_name)
    {
        parent::__construct($interface, $source, $interface_name);

        self::$existing_interfaces[$interface_name] = true;
        self::$existing_interfaces_ci[strtolower($interface_name)] = true;

        if ($interface->extends) {
            self::$parent_interfaces[$interface_name] = [];

            foreach ($interface->extends as $extended_interface) {
                $extended_interface_name = self::getAbsoluteClassFromName($extended_interface, $this->namespace, $this->aliased_classes);
                self::$parent_interfaces[$interface_name][] = $extended_interface_name;
            }
        }
    }

    /**
     * @param  string $interface
     * @return boolean
     */
    public static function interfaceExists($interface)
    {
        if (isset(self::$existing_interfaces_ci[strtolower($interface)])) {
            return self::$existing_interfaces_ci[strtolower($interface)];
        }

        if (in_array($interface, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (interface_exists($interface, true)) {
            $reflected_interface = new \ReflectionClass($interface);

            self::$existing_interfaces_ci[strtolower($interface)] = true;
            self::$existing_interfaces[$reflected_interface->getName()] = true;
            return true;
        }

        self::$existing_interfaces_ci[strtolower($interface)] = false;
        self::$existing_interfaces_ci[$interface] = false;

        return false;
    }

    /**
     * @param  string  $interface
     * @return boolean
     */
    public static function hasCorrectCasing($interface)
    {
        if (!self::interfaceExists(strtolower($interface))) {
            throw new \InvalidArgumentException('Cannot check casing on nonexistent class ' . $interface);
        }

        return isset(self::$existing_interfaces[$interface]);
    }

    public static function clearCache()
    {
        self::$existing_interfaces = [];
        self::$existing_interfaces_ci = [];
    }

    public static function interfaceExtends($interface_name, $possible_parent)
    {
        return in_array($possible_parent, self::getParentInterfaces($interface_name));
    }

    /**
     * @param  string $interface_name
     * @return array<string>   all interfaces extended by $interface_name
     */
    public static function getParentInterfaces($interface_name)
    {
        self::registerClass($interface_name);

        $extended_interfaces = [];

        if (!isset(self::$parent_interfaces[$interface_name])) {
            return [];
        }

        foreach (self::$parent_interfaces[$interface_name] as $extended_interface_name) {
            $extended_interfaces[] = $extended_interface_name;

            $extended_interfaces = array_merge(
                self::getParentInterfaces($extended_interface_name),
                $extended_interfaces
            );
        }

        return $extended_interfaces;
    }
}
