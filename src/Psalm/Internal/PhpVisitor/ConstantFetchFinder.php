<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use Override;
use PhpParser;

/**
 * Finds the constants an initializer reads when PHP evaluates it, not those of the closures it holds (PHP 8.5)
 *
 * @internal
 */
final class ConstantFetchFinder extends PhpParser\NodeVisitorAbstract
{
    /** @var list<PhpParser\Node\Expr\ConstFetch|PhpParser\Node\Expr\ClassConstFetch> */
    private array $fetches = [];

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr\Closure || $node instanceof PhpParser\Node\Expr\ArrowFunction) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof PhpParser\Node\Expr\ConstFetch || $node instanceof PhpParser\Node\Expr\ClassConstFetch) {
            $this->fetches[] = $node;
        }

        return null;
    }

    /**
     * @return list<PhpParser\Node\Expr\ConstFetch|PhpParser\Node\Expr\ClassConstFetch>
     */
    public static function find(PhpParser\Node\Expr $expr): array
    {
        $visitor = new self();
        (new PhpParser\NodeTraverser($visitor))->traverse([$expr]);

        return $visitor->fetches;
    }
}
