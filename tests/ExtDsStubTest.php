<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExtDsStubTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'Hashable equals takes the object as $obj' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Hashable;

                    final class Key implements Hashable {
                        public function __construct(private string $name) {}

                        #[Override]
                        public function equals($obj): bool {
                            return $obj instanceof self && $obj->name === $this->name;
                        }

                        #[Override]
                        public function hash(): string {
                            return $this->name;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'a vector and a deque have the methods of a sequence' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Deque;
                    use Ds\Vector;

                    /**
                     * @param Vector<int> $vector
                     * @param Deque<int> $deque
                     */
                    function fill(Vector $vector, Deque $deque): void {
                        $vector->push(1);
                        $vector->unshift(0);
                        $vector->insert(1, 5);
                        $vector->set(0, 2);
                        $vector->apply(fn(int $value): int => $value * 2);
                        $vector->sort(fn(int $a, int $b): int => $a <=> $b);

                        $deque->push(1);
                        $deque->unshift(0);
                        $deque->insert(1, 5);
                        $deque->set(0, 2);
                        $deque->apply(fn(int $value): int => $value * 2);
                        $deque->sort();
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'iterating a map, a set, a vector or a deque is mutation-free' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Deque;
                    use Ds\Map;
                    use Ds\Set;
                    use Ds\Vector;

                    /** @psalm-immutable */
                    final class Permissions {
                        /**
                         * @param Map<string, bool> $byRole
                         * @param Set<string> $flags
                         * @param Vector<string> $names
                         * @param Deque<string> $queue
                         */
                        public function __construct(
                            private Map $byRole,
                            private Set $flags,
                            private Vector $names,
                            private Deque $queue,
                        ) {}

                        /** @return list<string> */
                        public function all(): array {
                            $all = [];

                            foreach ($this->byRole as $role => $allowed) {
                                if ($allowed) {
                                    $all[] = $role;
                                }
                            }

                            foreach ($this->flags as $flag) {
                                $all[] = $flag;
                            }

                            foreach ($this->names as $name) {
                                $all[] = $name;
                            }

                            foreach ($this->queue as $item) {
                                $all[] = $item;
                            }

                            return $all;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'Hashable equals with another parameter name is a ParamNameMismatch' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Hashable;

                    final class Key implements Hashable {
                        #[Override]
                        public function equals($other): bool {
                            return $other === $this;
                        }

                        #[Override]
                        public function hash(): string {
                            return "key";
                        }
                    }
                    PHP,
                'error_message' => 'ParamNameMismatch',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'pushing a value of another type to a vector' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Vector;

                    /** @param Vector<int> $vector */
                    function add(Vector $vector): void {
                        $vector->push('a');
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'iterating a stack removes its values' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Stack;

                    /** @psalm-immutable */
                    final class Pending {
                        /** @param Stack<string> $stack */
                        public function __construct(private Stack $stack) {}

                        public function count(): int {
                            $count = 0;

                            foreach ($this->stack as $_) {
                                $count++;
                            }

                            return $count;
                        }
                    }
                    PHP,
                'error_message' => 'ImpureMethodCall',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'iterating a priority queue removes its values' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\PriorityQueue;

                    /** @psalm-immutable */
                    final class Jobs {
                        /** @param PriorityQueue<string> $queue */
                        public function __construct(private PriorityQueue $queue) {}

                        public function first(): ?string {
                            foreach ($this->queue as $job) {
                                return $job;
                            }

                            return null;
                        }
                    }
                    PHP,
                'error_message' => 'ImpureMethodCall',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
