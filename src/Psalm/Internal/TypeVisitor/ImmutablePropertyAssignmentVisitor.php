<?php
namespace Psalm\Internal\TypeVisitor;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\IssueBuffer;
use Psalm\Issue\ImpurePropertyAssignment;
use Psalm\Type\NodeVisitor;
use Psalm\Type\Union;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;

class ImmutablePropertyAssignmentVisitor extends NodeVisitor
{
    private $statements_analyzer;
    private $stmt;

    public function __construct(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\PropertyFetch $stmt
    ) {
        $this->statements_analyzer = $statements_analyzer;
        $this->stmt = $stmt;
    }

    public function enterNode(TypeNode $type) : ?int
    {
        if ($type instanceof Union && $type->reference_free) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TNamedObject) {
            $codebase = $this->statements_analyzer->getCodebase();

            $object_storage = $codebase->classlike_storage_provider->get(
                $type->value
            );

            if (!$object_storage->mutation_free) {
                if (IssueBuffer::accepts(
                    new ImpurePropertyAssignment(
                        'Cannot store a reference to an externally-mutable object'
                            . ' inside an immutable object – consider using __clone',
                        new CodeLocation($this->statements_analyzer, $this->stmt)
                    ),
                    $this->statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        return null;
    }
}
