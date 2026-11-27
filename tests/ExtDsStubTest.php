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
            'reading a map, a sequence or a set by offset gives its value type' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Deque;
                    use Ds\Map;
                    use Ds\Set;
                    use Ds\Vector;

                    /** @var Map<string, int> */
                    $map = new Map(['a' => 17]);
                    /** @var Vector<string> */
                    $vector = new Vector(['x']);
                    /** @var Deque<float> */
                    $deque = new Deque([1.5]);
                    /** @var Set<bool> */
                    $set = new Set([true]);

                    $fromMap = $map['a'];
                    $fromMapOrDefault = $map['b'] ?? 35;
                    $map['c'] ??= 35;
                    $fromVector = $vector[0];
                    $fromDeque = $deque[0];
                    $fromSet = $set[0];
                    $mapHasKey = isset($map['a']);
                    $vectorHasIndex = isset($vector[0]);
                    PHP,
                'assertions' => [
                    '$fromMap===' => 'int',
                    '$fromMapOrDefault===' => 'int',
                    '$fromVector===' => 'string',
                    '$fromDeque===' => 'float',
                    '$fromSet===' => 'bool',
                    '$mapHasKey===' => 'bool',
                    '$vectorHasIndex===' => 'bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'reading a sequence by offset gives its value type' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Sequence;

                    /** @param Sequence<int> $sequence */
                    function first(Sequence $sequence): int {
                        $first = $sequence[0];
                        /** @psalm-check-type-exact $first = int */
                        return $first;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'writing to a map, a sequence, a set, a stack or a queue by offset' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Deque;
                    use Ds\Map;
                    use Ds\Queue;
                    use Ds\Set;
                    use Ds\Stack;
                    use Ds\Vector;

                    /**
                     * @param Map<string, int> $map
                     * @param Vector<string> $vector
                     * @param Deque<string> $deque
                     * @param Set<string> $set
                     * @param Stack<string> $stack
                     * @param Queue<string> $queue
                     */
                    function fill(Map $map, Vector $vector, Deque $deque, Set $set, Stack $stack, Queue $queue): void {
                        $map['a'] = 1;
                        $vector[] = 'a';
                        $vector[0] = 'b';
                        $deque[] = 'a';
                        $deque[0] = 'b';
                        $set[] = 'a';
                        $stack[] = 'a';
                        $queue[] = 'a';
                        unset($map['a'], $vector[0], $deque[0]);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'reading a map, a sequence or a set by offset is mutation-free' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Map;
                    use Ds\Set;
                    use Ds\Vector;

                    /**
                     * @param Map<string, int> $map
                     * @param Vector<int> $vector
                     * @param Set<int> $set
                     * @psalm-pure
                     */
                    function sum(Map $map, Vector $vector, Set $set): int {
                        return $map['a'] + $vector[0] + $set[0] + (isset($map['b']) ? 1 : 0);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'a collection serializes to an array' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Map;
                    use Ds\Vector;

                    /** @var Vector<string> */
                    $vector = new Vector(['x']);
                    /** @var Map<string, int> */
                    $map = new Map(['a' => 17]);

                    $vectorJson = $vector->jsonSerialize();
                    $mapJson = $map->jsonSerialize();
                    PHP,
                'assertions' => [
                    '$vectorJson===' => 'array<int, string>',
                    '$mapJson===' => 'array<array-key, mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.3',
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
            'writing a value of another type to a map by key' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Map;

                    /** @param Map<string, int> $map */
                    function add(Map $map): void {
                        $map['a'] = 'b';
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'appending to a map' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Map;

                    /** @param Map<string, int> $map */
                    function add(Map $map): void {
                        $map[] = 1;
                    }
                    PHP,
                'error_message' => 'NullArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'writing to a vector by a string key' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Vector;

                    /** @param Vector<int> $vector */
                    function add(Vector $vector): void {
                        $vector['a'] = 1;
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'appending a value of another type to a vector' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Vector;

                    /** @param Vector<int> $vector */
                    function add(Vector $vector): void {
                        $vector[] = 'a';
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'writing to a set by index' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Set;

                    /** @param Set<int> $set */
                    function add(Set $set): void {
                        $set[0] = 1;
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'unsetting an index of a set' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Set;

                    /** @param Set<int> $set */
                    function remove(Set $set): void {
                        unset($set[0]);
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'reading a stack by index' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Stack;

                    /** @param Stack<int> $stack */
                    function top(Stack $stack): void {
                        $stack[0];
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'checking an index of a queue' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\Queue;

                    /** @param Queue<int> $queue */
                    function has(Queue $queue): bool {
                        return isset($queue[0]);
                    }
                    PHP,
                'error_message' => 'InvalidArgument',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
            'reading a priority queue by index' => [
                'code' => <<<'PHP'
                    <?php
                    use Ds\PriorityQueue;

                    /** @param PriorityQueue<int> $queue */
                    function first(PriorityQueue $queue): void {
                        $queue[0];
                    }
                    PHP,
                'error_message' => 'InvalidArrayAccess',
                'error_levels' => [],
                'php_version' => '8.3',
            ],
        ];
    }
}
