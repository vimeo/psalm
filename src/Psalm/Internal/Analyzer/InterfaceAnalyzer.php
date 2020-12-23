<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Issue\UndefinedInterface;
use function strtolower;
use function explode;
use function array_pop;
use function implode;

/**
 * @internal
 */
class InterfaceAnalyzer extends ClassLikeAnalyzer
{
    public function __construct(
        PhpParser\Node\Stmt\Interface_ $interface,
        SourceAnalyzer $source,
        string $fq_interface_name
    ) {
        parent::__construct($interface, $source, $fq_interface_name);
    }

    public function analyze(): void
    {
        if (!$this->class instanceof PhpParser\Node\Stmt\Interface_) {
            throw new \LogicException('Something went badly wrong');
        }

        $class = $this->class;

        $storage = $this->storage;

        if ($storage->has_visitor_issues) {
            return;
        }

        $project_analyzer = $this->file_analyzer->project_analyzer;
        $codebase = $project_analyzer->getCodebase();
        $config = $project_analyzer->getConfig();

        if ($codebase->alter_code && $class->name && $codebase->classes_to_move) {
            if (isset($codebase->classes_to_move[strtolower($this->fq_class_name)])) {
                $destination_class = $codebase->classes_to_move[strtolower($this->fq_class_name)];

                $source_class_parts = explode('\\', $this->fq_class_name);
                $destination_class_parts = explode('\\', $destination_class);

                array_pop($source_class_parts);
                array_pop($destination_class_parts);

                $source_ns = implode('\\', $source_class_parts);
                $destination_ns = implode('\\', $destination_class_parts);

                if (strtolower($source_ns) !== strtolower($destination_ns)) {
                    // If the trait already has a namespace
                    if ($storage->namespace_name_location) {
                        $bounds = $storage->namespace_name_location->getSelectionBounds();

                        $file_manipulations = [
                            new \Psalm\FileManipulation(
                                $bounds[0],
                                $bounds[1],
                                $destination_ns
                            )
                        ];

                        \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    } elseif (!$source_ns) {
                        $first_statement_pos = $this->getFileAnalyzer()->getFirstStatementOffset();

                        if ($first_statement_pos === -1) {
                            $first_statement_pos = (int) $class->getAttribute('startFilePos');
                        }

                        $file_manipulations = [
                            new \Psalm\FileManipulation(
                                $first_statement_pos,
                                $first_statement_pos,
                                'namespace ' . $destination_ns . ';' . "\n\n",
                                true
                            )
                        ];

                        \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                            $this->getFilePath(),
                            $file_manipulations
                        );
                    }
                }
            }

            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $this,
                $class->name,
                $this->fq_class_name,
                null
            );
        }

        if ($this->class->extends) {
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

        $fq_interface_name = $this->getFQCLN();

        if (!$fq_interface_name) {
            throw new \UnexpectedValueException('bad');
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_interface_name);

        foreach ($class_storage->attributes as $attribute) {
            AttributeAnalyzer::analyze(
                $this,
                $attribute,
                $class_storage->suppressed_issues + $this->getSuppressedIssues(),
                1,
                $class_storage
            );
        }

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $type_provider = new \Psalm\Internal\Provider\NodeDataProvider();

                $method_analyzer->analyze(new \Psalm\Context($this->getFQCLN()), $type_provider);

                $actual_method_id = $method_analyzer->getMethodId();

                if ($stmt->name->name !== '__construct'
                    && $config->reportIssueInFile('InvalidReturnType', $this->getFilePath())
                ) {
                    ClassAnalyzer::analyzeClassMethodReturnType(
                        $stmt,
                        $method_analyzer,
                        $this,
                        $type_provider,
                        $codebase,
                        $class_storage,
                        $fq_interface_name,
                        $actual_method_id,
                        $actual_method_id,
                        false
                    );
                }
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
