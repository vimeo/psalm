<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\StatementsSource;

class InterfaceChecker extends ClassLikeChecker
{
    /**
     * @param PhpParser\Node\Stmt\Interface_ $interface
     * @param StatementsSource               $source
     * @param string                         $fq_interface_name
     */
    public function __construct(PhpParser\Node\Stmt\Interface_ $interface, StatementsSource $source, $fq_interface_name)
    {
        parent::__construct($interface, $source, $fq_interface_name);
    }

    /**
     * @return void
     */
    public function analyze()
    {
        if (!$this->class instanceof PhpParser\Node\Stmt\Interface_) {
            throw new \LogicException('Something went badly wrong');
        }

        if ($this->class->extends) {
            foreach ($this->class->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this->getAliases()
                );

                $parent_reference_location = new CodeLocation($this, $extended_interface);

                $project_checker = $this->file_checker->project_checker;

                if (!ClassLikeChecker::classOrInterfaceExists(
                    $project_checker,
                    $extended_interface_name,
                    $parent_reference_location
                )) {
                    // we should not normally get here
                    return;
                }
            }
        }
    }

    /**
     * @param  ProjectChecker $project_checker
     * @param  string         $fq_interface_name
     *
     * @return bool
     */
    public static function interfaceExists(ProjectChecker $project_checker, $fq_interface_name)
    {
        if (isset(self::$SPECIAL_TYPES[strtolower($fq_interface_name)])) {
            return false;
        }

        return $project_checker->codebase->hasFullyQualifiedInterfaceName($fq_interface_name);
    }

    /**
     * @param  ProjectChecker $project_checker
     * @param  string         $interface_name
     * @param  string         $possible_parent
     *
     * @return bool
     */
    public static function interfaceExtends(ProjectChecker $project_checker, $interface_name, $possible_parent)
    {
        return in_array($possible_parent, self::getParentInterfaces($project_checker, $interface_name), true);
    }

    /**
     * @param  ProjectChecker $project_checker
     * @param  string         $fq_interface_name
     *
     * @return array<string>   all interfaces extended by $interface_name
     */
    public static function getParentInterfaces(ProjectChecker $project_checker, $fq_interface_name)
    {
        $fq_interface_name = strtolower($fq_interface_name);

        $storage = $project_checker->classlike_storage_provider->get($fq_interface_name);

        return $storage->parent_interfaces;
    }
}
