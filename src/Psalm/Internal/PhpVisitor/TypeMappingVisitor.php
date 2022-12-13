<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Psalm\Internal\Provider\NodeDataProvider;

/**
 * @internal
 */
class TypeMappingVisitor extends NodeVisitorAbstract
{
    private NodeDataProvider $fake_type_provider;
    private NodeDataProvider $real_type_provider;

    public function __construct(
        NodeDataProvider $fake_type_provider,
        NodeDataProvider $real_type_provider
    ) {
        $this->fake_type_provider = $fake_type_provider;
        $this->real_type_provider = $real_type_provider;
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
