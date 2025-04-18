<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser;
use ReflectionClass;
use Throwable;

use function count;
use function end;
use function explode;
use function trait_exists;

/**
 * Given a list of file diffs, this scans an AST to find the sections it can replace, and parses
 * just those methods.
 *
 * @internal
 */
final class TraitFinder extends PhpParser\NodeVisitorAbstract
{
    /** @var list<PhpParser\Node\Stmt\Trait_> */
    private array $matching_trait_nodes = [];

    public function __construct(
        private readonly string $fq_trait_name,
    ) {
    }

    public function enterNode(PhpParser\Node $node, bool &$traverseChildren = true): ?int
    {
        if ($node instanceof PhpParser\Node\Stmt\Trait_) {
            /** @var ?string */
            $resolved_name = $node->getAttribute('resolvedName');

            if ($resolved_name === null) {
                // compare ends of names, a temporary hack because PHPParser caches
                // may not have that attribute

                $fq_trait_name_parts = explode('\\', $this->fq_trait_name);

                /** @psalm-suppress PossiblyNullPropertyFetch */
                if ($node->name->name === end($fq_trait_name_parts)) {
                    $this->matching_trait_nodes[] = $node;
                }
            } elseif ($resolved_name === $this->fq_trait_name) {
                $this->matching_trait_nodes[] = $node;
            }
        }

        if ($node instanceof PhpParser\Node\Stmt\ClassLike
            || $node instanceof PhpParser\Node\FunctionLike
        ) {
            return PhpParser\NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    public function getNode(): ?PhpParser\Node\Stmt\Trait_
    {
        if (!count($this->matching_trait_nodes)) {
            return null;
        }

        if (count($this->matching_trait_nodes) === 1 || !trait_exists($this->fq_trait_name)) {
            return $this->matching_trait_nodes[0];
        }

        try {
            $reflection_trait = new ReflectionClass($this->fq_trait_name);
        } catch (Throwable) {
            return null;
        }

        foreach ($this->matching_trait_nodes as $node) {
            if ($node->getLine() === $reflection_trait->getStartLine()) {
                return $node;
            }
        }

        return null;
    }
}
