<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Psalm\Plugin\EventHandler\AfterFileParsedInterface;
use Psalm\Plugin\EventHandler\Event\AfterFileParsedEvent;

/**
 * Replaces a function or method that has `#[ReturnsDoc('type')]` with a copy that has a `@return type` docblock, and
 * marks the namespaced name of each class with an `afterFileParsed` attribute
 *
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class ReturnsDocAttributeToDocblock extends NodeVisitorAbstract implements AfterFileParsedInterface
{
    #[Override]
    public static function afterFileParsed(AfterFileParsedEvent $event): void
    {
        /** @var list<Stmt> */
        $stmts = (new NodeTraverser(new self()))->traverse($event->getStmts());
        $event->setStmts($stmts);
    }

    #[Override]
    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Stmt\Class_) {
            /** @var Name|null */
            $namespaced_name = $node->getAttribute('namespacedName');
            if ($namespaced_name !== null) {
                $namespaced_name->setAttribute('afterFileParsed', true);
            }

            return null;
        }

        if (!$node instanceof Stmt\Function_ && !$node instanceof Stmt\ClassMethod) {
            return null;
        }

        foreach ($node->attrGroups as $attr_group) {
            foreach ($attr_group->attrs as $attr) {
                if ($attr->name->toString() === 'ReturnsDoc'
                    && isset($attr->args[0])
                    && $attr->args[0]->value instanceof String_
                ) {
                    $copy = clone $node;
                    $copy->setDocComment(new Doc(
                        '/** @return ' . $attr->args[0]->value->value . ' */',
                        $attr->getStartLine(),
                        $attr->getStartFilePos(),
                        $attr->getStartTokenPos(),
                        $attr->getEndLine(),
                        $attr->getEndFilePos(),
                        $attr->getEndTokenPos(),
                    ));

                    return $copy;
                }
            }
        }

        return null;
    }
}
