<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\Path;

use function abs;
use function array_key_last;
use function array_keys;
use function array_sum;
use function count;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-props|write-refs
 */
abstract class DataFlowGraph
{
    /** @var array<string, array<string, Path>> */
    protected array $forward_edges = [];

    abstract public function addNode(DataFlowNode $node): void;

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function addPath(
        DataFlowNode $from,
        DataFlowNode $to,
        string $path_type,
        int $added_taints = 0,
        int $removed_taints = 0,
    ): void {
        $from_id = $from->id;
        $to_id = $to->id;

        if ($from_id === $to_id) {
            return;
        }

        $length = 0;

        if ($from->code_location
            && $to->code_location
            && $from->code_location->file_path === $to->code_location->file_path
        ) {
            $to_line = $to->code_location->raw_line_number;
            $from_line = $from->code_location->raw_line_number;
            $length = abs($to_line - $from_line);
        }

        $this->forward_edges[$from_id][$to_id] = new Path($path_type, $length, $added_taints, $removed_taints);
    }

    /**
     * @param array<string> $previous_path_types
     * @psalm-pure
     */
    protected static function shouldIgnoreFetch(
        string $path_type,
        string $expression_type,
        array $previous_path_types,
    ): bool {
        $el = strlen($expression_type);

        // arraykey-fetch requires a matching arraykey-assignment at the same level
        // otherwise the tainting is not valid
        if (str_starts_with($path_type, $expression_type . '-fetch-')
            || ($path_type === 'arraykey-fetch' && $expression_type === 'arrayvalue')
        ) {
            $fetch_nesting = 0;

            for ($x = count($previous_path_types)-1; $x >= 0; $x--) {
                $previous_path_type = $previous_path_types[$x];
                if ($previous_path_type === $expression_type . '-assignment') {
                    if ($fetch_nesting === 0) {
                        // a value assigned under any key: the keys of the array don't hold it
                        return $path_type === 'arraykey-fetch';
                    }

                    $fetch_nesting--;
                }

                if (str_starts_with($previous_path_type, $expression_type . '-fetch')) {
                    $fetch_nesting++;
                }

                if (str_starts_with($previous_path_type, $expression_type . '-assignment-')) {
                    if ($fetch_nesting > 0) {
                        $fetch_nesting--;
                        continue;
                    }

                    return !self::keysMayBeEqual(substr($previous_path_type, $el + 12), substr($path_type, $el + 7));
                }
            }
        }

        return false;
    }

    /**
     * Whether the array key or property assigned by an edge of type $expression_type . '-assignment-' .
     * $assigned_key and the one fetched by an edge of type $expression_type . '-fetch-' . $fetched_key can be
     * the same.
     *
     * An array key known exactly is quoted ('k'), and one whose start only is known is quoted and followed by
     * a star ('k'*: any key starting with k). A property name is bare. An array key fetch fetches the key ''.
     *
     * @psalm-pure
     */
    public static function keysMayBeEqual(string $assigned_key, string $fetched_key): bool
    {
        $assigned_prefix = self::getKeyPrefix($assigned_key);
        $fetched_prefix = self::getKeyPrefix($fetched_key);

        if ($assigned_prefix === null && $fetched_prefix === null) {
            return $assigned_key === $fetched_key;
        }

        if ($assigned_prefix === null) {
            return str_starts_with(self::getKeyValue($assigned_key), $fetched_prefix);
        }

        if ($fetched_prefix === null) {
            return str_starts_with(self::getKeyValue($fetched_key), $assigned_prefix);
        }

        return str_starts_with($assigned_prefix, $fetched_prefix) || str_starts_with($fetched_prefix, $assigned_prefix);
    }

    /**
     * The start of the array key of a path type whose key only that is known (see keysMayBeEqual())
     *
     * @psalm-pure
     */
    private static function getKeyPrefix(string $key): ?string
    {
        return strlen($key) > 3 && str_starts_with($key, "'") && str_ends_with($key, "'*")
            ? substr($key, 1, -2)
            : null;
    }

    /**
     * The array key or property name of a path type whose key is known exactly (see keysMayBeEqual())
     *
     * @psalm-pure
     */
    private static function getKeyValue(string $key): string
    {
        return strlen($key) > 1 && str_starts_with($key, "'") && str_ends_with($key, "'")
            ? substr($key, 1, -1)
            : $key;
    }

    /**
     * Whether an edge of type $path_type, from an array to the array it becomes once its value under a key is
     * replaced (see ArrayAssignmentAnalyzer::getOverwritePathType()), drops a flow whose open assignments are
     * $open_assignments: a flow of that value, the innermost of them being the assignment to that key. A flow of
     * the array's other values, of its keys or of all of it goes on, also one whose key isn't known, which may be
     * that one.
     *
     * @param list<string> $open_assignments
     * @psalm-pure
     */
    protected static function isOverwritten(string $path_type, array $open_assignments): bool
    {
        if (!str_starts_with($path_type, 'arrayvalue-overwrite-')) {
            return false;
        }

        $innermost = array_key_last($open_assignments);

        return $innermost !== null
            && $open_assignments[$innermost] === 'arrayvalue-assignment-' . substr($path_type, 21);
    }

    /**
     * @return array{int, int, int, float}
     * @psalm-mutation-free
     */
    public function getEdgeStats(): array
    {
        $lengths = 0;

        $destination_counts = [];
        $origin_counts = [];

        foreach ($this->forward_edges as $from_id => $destinations) {
            foreach ($destinations as $to_id => $path) {
                if ($path->length === 0) {
                    continue;
                }

                $lengths += $path->length;

                if (!isset($destination_counts[$to_id])) {
                    $destination_counts[$to_id] = 0;
                }

                $destination_counts[$to_id]++;

                $origin_counts[$from_id] = true;
            }
        }

        $count = array_sum($destination_counts);

        if (!$count) {
            return [0, 0, 0, 0.0];
        }

        $mean = $lengths / $count;

        return [$count, count($origin_counts), count($destination_counts), $mean];
    }

    /**
     * @psalm-return list<list<string>>
     * @psalm-mutation-free
     */
    public function summarizeEdges(): array
    {
        $edges = [];

        foreach ($this->forward_edges as $source => $destinations) {
            $edges[] = [$source, ...array_keys($destinations)];
        }

        return $edges;
    }
}
