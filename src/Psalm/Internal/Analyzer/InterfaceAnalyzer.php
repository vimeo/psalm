<?php

namespace Psalm\Internal\Analyzer;

use InvalidArgumentException;
use LogicException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\Statements\Expression\ClassConstAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InheritorViolation;
use Psalm\Issue\ParseError;
use Psalm\Issue\UndefinedInterface;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use UnexpectedValueException;

use function strtolower;

/**
 * @internal
 */
final class InterfaceAnalyzer extends ClassLikeAnalyzer
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
            throw new LogicException('Something went badly wrong');
        }

        $project_analyzer = $this->file_analyzer->project_analyzer;
        $codebase = $project_analyzer->getCodebase();
        $config = $project_analyzer->getConfig();

        $fq_interface_name = $this->getFQCLN();

        if (!$fq_interface_name) {
            throw new UnexpectedValueException('bad');
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_interface_name);

        if ($this->class->extends) {
            foreach ($this->class->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this->getAliases(),
                );

                $parent_reference_location = new CodeLocation($this, $extended_interface);

                if (!$codebase->classOrInterfaceExists(
                    $extended_interface_name,
                    $parent_reference_location,
                )) {
                    // we should not normally get here
                    return;
                }

                try {
                    $extended_interface_storage = $codebase->classlike_storage_provider->get($extended_interface_name);
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                $code_location = new CodeLocation(
                    $this,
                    $extended_interface,
                );

                if (!$extended_interface_storage->is_interface) {
                    IssueBuffer::maybeAdd(
                        new UndefinedInterface(
                            $extended_interface_name . ' is not an interface',
                            $code_location,
                            $extended_interface_name,
                        ),
                        $this->getSuppressedIssues(),
                    );
                }

                if ($codebase->store_node_types && $extended_interface_name) {
                    $bounds = $parent_reference_location->getSelectionBounds();

                    $codebase->analyzer->addOffsetReference(
                        $this->getFilePath(),
                        $bounds[0],
                        $bounds[1],
                        $extended_interface_name,
                    );
                }

                $this->checkTemplateParams(
                    $codebase,
                    $class_storage,
                    $extended_interface_storage,
                    $code_location,
                    $class_storage->template_type_extends_count[$extended_interface_name] ?? 0,
                );
            }
        }

        $class_union = new Union([new TNamedObject($fq_interface_name)]);
        foreach ($class_storage->direct_interface_parents as $parent_interface) {
            $parent_storage = $codebase->classlikes->getStorageFor($parent_interface);
            if ($parent_storage && $parent_storage->inheritors) {
                if (!UnionTypeComparator::isContainedBy($codebase, $class_union, $parent_storage->inheritors)) {
                    IssueBuffer::maybeAdd(
                        new InheritorViolation(
                            'Interface ' . $fq_interface_name . '
                             is not an allowed inheritor of parent interface ' . $parent_interface,
                            new CodeLocation($this, $this->class),
                        ),
                        $this->getSuppressedIssues(),
                    );
                }
            }
        }

        $fq_interface_name = $this->getFQCLN();

        if (!$fq_interface_name) {
            throw new UnexpectedValueException('bad');
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_interface_name);
        $interface_context = new Context($this->getFQCLN());

        AttributesAnalyzer::analyze(
            $this,
            $interface_context,
            $class_storage,
            $this->class->attrGroups,
            AttributesAnalyzer::TARGET_CLASS,
            $class_storage->suppressed_issues + $this->getSuppressedIssues(),
        );

        foreach ($class_storage->docblock_issues as $docblock_issue) {
            IssueBuffer::maybeAdd($docblock_issue);
        }

        $member_stmts = [];
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $type_provider = new NodeDataProvider();

                $method_analyzer->analyze($interface_context, $type_provider);

                $actual_method_id = $method_analyzer->getMethodId();

                if ($stmt->name->name !== '__construct'
                    && $stmt->name->name !== '__destruct'
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
                        false,
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                IssueBuffer::maybeAdd(
                    new ParseError(
                        'Interfaces cannot have properties',
                        new CodeLocation($this, $stmt),
                    ),
                );

                return;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $member_stmts[] = $stmt;

                foreach ($stmt->consts as $const) {
                    $const_id = strtolower($this->fq_class_name) . '::' . $const->name;

                    foreach ($codebase->class_constants_to_rename as $original_const_id => $new_const_name) {
                        if ($const_id === $original_const_id) {
                            $file_manipulations = [
                                new FileManipulation(
                                    (int) $const->name->getAttribute('startFilePos'),
                                    (int) $const->name->getAttribute('endFilePos') + 1,
                                    $new_const_name,
                                ),
                            ];

                            FileManipulationBuffer::add(
                                $this->getFilePath(),
                                $file_manipulations,
                            );
                        }
                    }
                }
            }
        }

        $statements_analyzer = new StatementsAnalyzer($this, new NodeDataProvider());
        $statements_analyzer->analyze($member_stmts, $interface_context, null, true);

        ClassConstAnalyzer::analyze($this->storage, $this->getCodebase());
    }
}
