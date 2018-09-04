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

        if (isset($class->isDuplicate)) {
            return false;
        }

        if ($this->class->extends) {
            foreach ($this->class->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this->getAliases()
                );

                $parent_reference_location = new CodeLocation($this, $extended_interface);

                $project_checker = $this->file_checker->project_checker;

                if (!$project_checker->codebase->classOrInterfaceExists(
                    $extended_interface_name,
                    $parent_reference_location
                )) {
                    // we should not normally get here
                    return;
                }
            }
        }
    }
}
