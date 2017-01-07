<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;

class InterfaceChecker extends ClassLikeChecker
{
    /**
     * @param PhpParser\Node\Stmt\ClassLike  $interface
     * @param StatementsSource               $source
     * @param string                         $interface_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $interface, StatementsSource $source, $interface_name)
    {
        if (!$interface instanceof PhpParser\Node\Stmt\Interface_) {
            throw new \InvalidArgumentException('Expecting an interface');
        }

        parent::__construct($interface, $source, $interface_name);

        $storage = self::$storage[$interface_name];

        self::$existing_interfaces[$interface_name] = true;
        self::$existing_interfaces_ci[strtolower($interface_name)] = true;

        if ($interface->extends) {
            foreach ($interface->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this
                );

                $source->getFileChecker()->evaluateClassLike($extended_interface_name);

                $storage->parent_interfaces[] = $extended_interface_name;
            }
        }
    }

    /**
     * @param  string $interface
     * @param  FileChecker  $file_checker
     * @return boolean
     */
    public static function interfaceExists($interface, FileChecker $file_checker)
    {
        if (isset(self::$SPECIAL_TYPES[$interface])) {
            return false;
        }

        if ($file_checker->evaluateClassLike($interface) === false) {
            return false;
        }

        if (isset(self::$existing_interfaces_ci[strtolower($interface)])) {
            return self::$existing_interfaces_ci[strtolower($interface)];
        }

        if (!isset(self::$existing_interfaces_ci[strtolower($interface)])) {
            // it exists, but it's not an interface
            self::$existing_interfaces_ci[strtolower($interface)] = false;
            return false;
        }

        return true;
    }

    /**
     * @param  string       $interface_name
     * @return boolean
     */
    public static function hasCorrectCasing($interface_name)
    {
        if (!isset(self::$existing_interfaces_ci[strtolower($interface_name)])) {
            throw new \UnexpectedValueException('Invalid storage for ' . $interface_name);
        }
        return isset(self::$existing_interfaces[$interface_name]);
    }

    /**
     * @param  string       $interface_name
     * @param  string       $possible_parent
     * @return boolean
     */
    public static function interfaceExtends($interface_name, $possible_parent)
    {
        return in_array($possible_parent, self::getParentInterfaces($interface_name));
    }

    /**
     * @param  string       $interface_name
     * @return array<string>   all interfaces extended by $interface_name
     */
    public static function getParentInterfaces($interface_name)
    {
        if (!isset(self::$storage[$interface_name])) {
            throw new \UnexpectedValueException('Invalid storage for ' . $interface_name);
        }

        $extended_interfaces = [];

        $storage = self::$storage[$interface_name];

        foreach ($storage->parent_interfaces as $extended_interface_name) {
            $extended_interfaces[] = $extended_interface_name;

            $extended_interfaces = array_merge(
                self::getParentInterfaces($extended_interface_name),
                $extended_interfaces
            );
        }

        return $extended_interfaces;
    }
}
