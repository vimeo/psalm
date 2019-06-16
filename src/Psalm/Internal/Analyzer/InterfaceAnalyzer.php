<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\UndefinedInterface;

/**
 * @internal
 */
class InterfaceAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @param PhpParser\Node\Stmt\Interface_ $interface
     * @param string                         $fq_interface_name
     */
    public function __construct(PhpParser\Node\Stmt\Interface_ $interface, SourceAnalyzer $source, $fq_interface_name)
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
            $project_analyzer = $this->file_analyzer->project_analyzer;
            $codebase = $project_analyzer->getCodebase();

            foreach ($this->class->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this->getAliases()
                );

                $parent_reference_location = new CodeLocation($this, $extended_interface);

                if (!$codebase->classOrInterfaceExists(
                    $extended_interface_name,
                    $parent_reference_location
                )) {
                    // we should not normally get here
                    return;
                }

                try {
                    $extended_interface_storage = $codebase->classlike_storage_provider->get($extended_interface_name);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                if (!$extended_interface_storage->is_interface) {
                    $code_location = new CodeLocation(
                        $this,
                        $extended_interface
                    );

                    if (\Psalm\IssueBuffer::accepts(
                        new UndefinedInterface(
                            $extended_interface_name . ' is not an interface',
                            $code_location,
                            $extended_interface_name
                        ),
                        $this->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($codebase->store_node_types && $extended_interface_name) {
                    $bounds = $parent_reference_location->getSelectionBounds();

                    $codebase->analyzer->addOffsetReference(
                        $this->getFilePath(),
                        $bounds[0],
                        $bounds[1],
                        $extended_interface_name
                    );
                }
            }
        }

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $method_analyzer->analyze(new \Psalm\Context($this->getFQCLN()));
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                \Psalm\IssueBuffer::add(
                    new \Psalm\Issue\ParseError(
                        'Interfaces cannot have properties',
                        new CodeLocation($this, $stmt)
                    )
                );

                return;
            }
        }
    }
}
