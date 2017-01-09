<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;

class InterfaceChecker extends ClassLikeChecker
{
    /**
     * @param PhpParser\Node\Stmt\ClassLike  $interface
     * @param StatementsSource               $source
     * @param string                         $fq_interface_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $interface, StatementsSource $source, $fq_interface_name)
    {
        if (!$interface instanceof PhpParser\Node\Stmt\Interface_) {
            throw new \InvalidArgumentException('Expecting an interface');
        }

        parent::__construct($interface, $source, $fq_interface_name);

        $fq_interface_name_lower = strtolower($fq_interface_name);

        $storage = self::$storage[$fq_interface_name_lower];

        $project_checker = $source->getFileChecker()->project_checker;
        $project_checker->addFullyQualifiedInterfaceName($fq_interface_name);

        if ($interface->extends) {
            foreach ($interface->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this
                );

                $source->getFileChecker()->evaluateClassLike($extended_interface_name, false);

                $storage->parent_interfaces[] = $extended_interface_name;
            }
        }
    }

    /**
     * @param  string       $fq_interface_name
     * @param  FileChecker  $file_checker
     * @param  bool         $visit_file
     * @return boolean
     */
    public static function interfaceExists($fq_interface_name, FileChecker $file_checker, $visit_file = false)
    {
        if (isset(self::$SPECIAL_TYPES[$fq_interface_name])) {
            return false;
        }

        if ($file_checker->evaluateClassLike($fq_interface_name, $visit_file) === false) {
            return false;
        }

        return $file_checker->project_checker->hasFullyQualifiedInterfaceName($fq_interface_name);
    }

    /**
     * @param  string       $fq_interface_name
     * @param  FileChecker  $file_checker
     * @return boolean
     */
    public static function hasCorrectCasing($fq_interface_name, FileChecker $file_checker)
    {
        return isset($file_checker->project_checker->existing_interfaces[$fq_interface_name]);
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
     * @param  string       $fq_interface_name
     * @return array<string>   all interfaces extended by $interface_name
     */
    public static function getParentInterfaces($fq_interface_name)
    {
        $fq_interface_name = strtolower($fq_interface_name);

        if (!isset(self::$storage[$fq_interface_name])) {
            throw new \UnexpectedValueException('Invalid storage for ' . $fq_interface_name);
        }

        $extended_interfaces = [];

        $storage = self::$storage[$fq_interface_name];

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
