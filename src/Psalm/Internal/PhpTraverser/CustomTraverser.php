<?php

declare(strict_types=1);

namespace Psalm\Internal\PhpTraverser;

use LogicException;
use PhpParser\Node;
use PhpParser\NodeTraverser;

use function array_pop;
use function array_splice;
use function gettype;
use function is_array;

/**
 * @internal
 */
final class CustomTraverser extends NodeTraverser
{
    public function __construct()
    {
        $this->stopTraversal = false;
    }

    /**
     * Recursively traverse a node.
     *
     * @param Node $node node to traverse
     */
    protected function traverseNode(Node $node): void
    {
        foreach ($node->getSubNodeNames() as $name) {
            $subNode = &$node->$name;

            if (is_array($subNode)) {
                $subNode = $this->traverseArray($subNode);
                if ($this->stopTraversal) {
                    break;
                }
            } elseif ($subNode instanceof Node) {
                $traverseChildren = true;
                foreach ($this->visitors as $visitor) {
                    $return = $visitor->enterNode($subNode, $traverseChildren);
                    if (null !== $return) {
                        if ($return instanceof Node) {
                            $subNode = $return;
                        } elseif (self::DONT_TRAVERSE_CHILDREN === $return) {
                            $traverseChildren = false;
                        } elseif (self::STOP_TRAVERSAL === $return) {
                            $this->stopTraversal = true;
                            break 2;
                        } else {
                            throw new LogicException(
                                'enterNode() returned invalid value of type ' . gettype($return),
                            );
                        }
                    }
                }

                if ($traverseChildren) {
                    $this->traverseNode($subNode);
                    if ($this->stopTraversal) {
                        break;
                    }
                }

                foreach ($this->visitors as $visitor) {
                    $return = $visitor->leaveNode($subNode);
                    if (null !== $return) {
                        if ($return instanceof Node) {
                            $subNode = $return;
                        } elseif (self::STOP_TRAVERSAL === $return) {
                            $this->stopTraversal = true;
                            break 2;
                        } elseif (is_array($return)) {
                            throw new LogicException(
                                'leaveNode() may only return an array ' .
                                'if the parent structure is an array',
                            );
                        } else {
                            throw new LogicException(
                                'leaveNode() returned invalid value of type ' . gettype($return),
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Recursively traverse array (usually of nodes).
     *
     * @param array $nodes Array to traverse
     * @return array Result of traversal (may be original array or changed one)
     */
    protected function traverseArray(array $nodes): array
    {
        $doNodes = [];

        foreach ($nodes as $i => &$node) {
            if ($node instanceof Node) {
                $traverseChildren = true;
                foreach ($this->visitors as $visitor) {
                    $return = $visitor->enterNode($node, $traverseChildren);
                    if (null !== $return) {
                        if ($return instanceof Node) {
                            $node = $return;
                        } elseif (self::DONT_TRAVERSE_CHILDREN === $return) {
                            $traverseChildren = false;
                        } elseif (self::STOP_TRAVERSAL === $return) {
                            $this->stopTraversal = true;
                            break 2;
                        } else {
                            throw new LogicException(
                                'enterNode() returned invalid value of type ' . gettype($return),
                            );
                        }
                    }
                }

                if ($traverseChildren) {
                    $this->traverseNode($node);
                    if ($this->stopTraversal) {
                        break;
                    }
                }

                foreach ($this->visitors as $visitor) {
                    $return = $visitor->leaveNode($node);
                    if (null !== $return) {
                        if ($return instanceof Node) {
                            $node = $return;
                        } elseif (is_array($return)) {
                            $doNodes[] = [$i, $return];
                            break;
                        } elseif (self::REMOVE_NODE === $return) {
                            $doNodes[] = [$i, []];
                            break;
                        } elseif (self::STOP_TRAVERSAL === $return) {
                            $this->stopTraversal = true;
                            break 2;
                        } elseif (false === $return) {
                            throw new LogicException(
                                'bool(false) return from leaveNode() no longer supported. ' .
                                'Return NodeVisitor::REMOVE_NODE instead',
                            );
                        } else {
                            throw new LogicException(
                                'leaveNode() returned invalid value of type ' . gettype($return),
                            );
                        }
                    }
                }
            } elseif (is_array($node)) {
                throw new LogicException('Invalid node structure: Contains nested arrays');
            }
        }

        if (!empty($doNodes)) {
            while ([$i, $replace] = array_pop($doNodes)) {
                array_splice($nodes, $i, 1, $replace);
            }
        }

        return $nodes;
    }
}
