<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Psalm\Internal\Provider\NodeDataProvider;

/**
 * @internal
 */
final class TypeMappingVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly NodeDataProvider $fake_type_provider,
        private readonly NodeDataProvider $real_type_provider,
    ) {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     */
    public function enterNode(Node $node)
    {
        $origNode = $node;

        /** @psalm-suppress ArgumentTypeCoercion */
        $node_type = $this->fake_type_provider->getType($origNode);

        if ($node_type) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $this->real_type_provider->setType($origNode, $node_type);
        }

        return null;
    }
}
