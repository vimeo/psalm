<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Attribute;
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
use Psalm\Interner;
use Psalm\Issue\InheritorViolation;
use Psalm\Issue\ParseError;
use Psalm\Issue\UndefinedInterface;
use Psalm\IssueBuffer;
use Psalm\StrId;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * @internal
 */
final class InterfaceAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        PhpParser\Node\Stmt\Interface_ $interface,
        SourceAnalyzer $source,
        int $fq_interface_name,
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

        self::registerDocblockSuppressions($this->storage, $this->getFilePath(), $codebase);

        $fq_interface_name = $this->getFQCLN();

        $class_storage = $codebase->classlike_storage_provider->get($fq_interface_name);

        $class_context = new Context($fq_interface_name);

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
                    $class_context,
                )) {
                    // we should not normally get here
                    return;
                }

                try {
                    $extended_interface_storage = $codebase->classlike_storage_provider->get($extended_interface_name);
                } catch (InvalidArgumentException) {
                    continue;
                }

                $code_location = new CodeLocation(
                    $this,
                    $extended_interface,
                );

                if (!$extended_interface_storage->is_interface) {
                    IssueBuffer::maybeAdd(
                        new UndefinedInterface(
                            Interner::str($extended_interface_name) . ' is not an interface',
                            $code_location,
                            $extended_interface_name,
                        ),
                        $this->getSuppressedIssues(),
                    );
                }

                if ($codebase->store_node_types) {
                    $bounds = $parent_reference_location->getSelectionBounds();

                    $codebase->analyzer->addOffsetReference(
                        $this->getFilePath(),
                        $bounds[0],
                        $bounds[1],
                        Interner::str($extended_interface_name),
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
                            'Interface ' . Interner::str($fq_interface_name) . '
                             is not an allowed inheritor of parent interface ' . Interner::str($parent_interface),
                            new CodeLocation($this, $this->class),
                        ),
                        $this->getSuppressedIssues(),
                    );
                }
            }
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_interface_name);
        $interface_context = new Context($this->getFQCLN());

        AttributesAnalyzer::analyze(
            $this,
            $interface_context,
            $class_storage,
            $this->class->attrGroups,
            Attribute::TARGET_CLASS,
            $class_storage->suppressed_issues + $this->getSuppressedIssues(),
        );

        foreach ($class_storage->docblock_issues as $docblock_issue) {
            IssueBuffer::maybeAdd($docblock_issue);
        }

        $member_stmts = [];
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_name_lc = Interner::internLower($stmt->name->name);
                if (!isset($class_storage->methods[$method_name_lc])) {
                    // Storage was overwritten by a different class-like with the same FQCN
                    // (e.g., project declares interface X while vendor has class X).
                    // Skip analysis — DuplicateClass was already emitted during scanning.
                    continue;
                }

                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $type_provider = new NodeDataProvider();

                $method_analyzer->analyze($interface_context, $type_provider);

                $actual_method_id = $method_analyzer->getMethodId();

                if ($method_name_lc !== StrId::__construct
                    && $method_name_lc !== StrId::__destruct
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
                        $class_context,
                    );
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                // PHP 8.4+ allows interface properties with hooks
                if ($codebase->analysis_php_version_id >= 8_04_00 && !empty($stmt->hooks)) {
                    continue;
                }

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
                    $new_const_name = $codebase->class_constants_to_rename[Interner::lower($this->fq_class_name)]
                        [Interner::intern($const->name->name)] ?? null;

                    if ($new_const_name !== null) {
                        $file_manipulations = [
                            new FileManipulation(
                                (int) $const->name->getAttribute('startFilePos'),
                                (int) $const->name->getAttribute('endFilePos') + 1,
                                Interner::str($new_const_name),
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

        $pseudo_methods = $class_storage->pseudo_methods + $class_storage->pseudo_static_methods;

        MethodComparator::comparePseudoMethods($pseudo_methods, $this->fq_class_name, $codebase, $class_storage);

        $statements_analyzer = new StatementsAnalyzer($this, new NodeDataProvider(), true);
        $statements_analyzer->analyze($member_stmts, $interface_context, null);

        ClassConstAnalyzer::analyze($this->storage, $this->getCodebase());
    }
}
