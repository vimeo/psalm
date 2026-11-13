<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\CodeLocation;
use Psalm\Internal\Codebase\TaintFlowGraph;

use function array_flip;
use function count;
use function is_bool;
use function is_int;
use function pack;
use function strlen;
use function strpos;
use function substr;
use function unpack;

/**
 * The nodes of a TaintFlowGraph, kept as rows: a node object with its location takes about 1.3 kB, and the graph of a
 * large code base holds hundreds of thousands of them, while the resolution needs little more than their ids, labels
 * and where they are, until it reports a flow. A row is a binary string:
 *
 * - a byte of flags (see the constants)
 * - the index of the node's label in $strings
 * - if it has a location: the index of its file in $file_paths and $file_names, its raw start and end offsets and
 *   its raw line number, then, if the location differs from one made of those alone, the index in $strings of what
 *   differs (see CodeLocation::getCompactState())
 * - if it has taints, the taints
 *
 * Its unspecialized id and specialization key, if any, are those its id is made of (see DataFlowNode::make()).
 * A node a row can't hold (with flow state, as the resolution makes them, or a location with a previous one) stays
 * a node object: every method here takes either.
 *
 * Rows only make sense with the table of the graph holding them: TaintFlowGraph::addGraph() imports those of
 * another graph.
 *
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-refs
 */
final class NodeTable
{
    private const SPECIALIZED = 1;
    private const LOCATED = 2;
    private const STATEFUL = 4;
    private const TAINTED = 8;

    private const LABEL_OFFSET = 1;
    private const FILE_OFFSET = 5;
    private const START_OFFSET = 9;
    private const END_OFFSET = 13;
    private const LINE_OFFSET = 17;
    private const STATE_OFFSET = 21;

    /**
     * The labels of the nodes and the states of their locations
     *
     * @var list<string>
     */
    public array $strings = [];

    /** @var list<string> */
    public array $file_paths = [];

    /** @var list<string> */
    public array $file_names = [];

    /** @var array<string, int> */
    private array $string_ids = [];

    /** @var array<string, int> */
    private array $file_ids = [];

    /**
     * @param list<string> $strings
     * @param list<string> $file_paths
     * @param list<string> $file_names
     * @psalm-pure
     */
    public static function fromLists(array $strings, array $file_paths, array $file_names): self
    {
        $table = new self();
        $table->strings = $strings;
        $table->file_paths = $file_paths;
        $table->file_names = $file_names;
        /** @var array<string, int> */
        $table->string_ids = array_flip($strings);
        /** @var array<string, int> */
        $table->file_ids = array_flip($file_paths);

        return $table;
    }

    /**
     * @psalm-mutation-free
     */
    public function isEmpty(): bool
    {
        return $this->strings === [] && $this->file_paths === [];
    }

    /**
     * @psalm-external-mutation-free
     */
    private function intern(string $string): int
    {
        $index = $this->string_ids[$string] ?? null;

        if ($index === null) {
            $index = count($this->strings);
            $this->strings[] = $string;
            $this->string_ids[$string] = $index;
        }

        return $index;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function internFile(string $file_path, string $file_name): int
    {
        $index = $this->file_ids[$file_path] ?? null;

        if ($index === null) {
            $index = count($this->file_paths);
            $this->file_paths[] = $file_path;
            $this->file_names[] = $file_name;
            $this->file_ids[$file_path] = $index;
        }

        return $index;
    }

    /**
     * The row of $node, or $node itself if a row can't hold it
     *
     * @psalm-external-mutation-free
     */
    public function encode(DataFlowNode $node): string|DataFlowNode
    {
        if ($node->taintSource !== null || $node->path_types !== [] || $node->context !== null) {
            return $node;
        }

        $flags = 0;

        if ($node->unspecialized_id !== null || $node->specialization_key !== null) {
            if ($node->unspecialized_id === null
                || $node->specialization_key === null
                || $node->id !== $node->unspecialized_id . TaintFlowGraph::SPECIALIZATION_SEPARATOR
                    . $node->specialization_key
                || strpos($node->id, TaintFlowGraph::SPECIALIZATION_SEPARATOR) !== strlen($node->unspecialized_id)
            ) {
                return $node;
            }

            $flags |= self::SPECIALIZED;
        }

        $location = '';
        $code_location = $node->code_location;

        if ($code_location !== null) {
            $state = $code_location->getCompactState();

            if ($state === null) {
                return $node;
            }

            $flags |= self::LOCATED;
            $location = pack(
                'Vlll',
                $this->internFile($code_location->file_path, $code_location->file_name),
                $code_location->raw_file_start,
                $code_location->raw_file_end,
                $code_location->raw_line_number,
            );

            if ($state !== []) {
                $flags |= self::STATEFUL;
                $location .= pack('V', $this->intern(self::encodeState($state)));
            }
        }

        $taints = '';

        if ($node->taints !== 0) {
            $flags |= self::TAINTED;
            $taints = pack('q', $node->taints);
        }

        return pack('CV', $flags, $this->intern($node->label)) . $location . $taints;
    }

    /**
     * The node of id $id with row $row
     *
     * @psalm-mutation-free
     */
    public function decode(string $id, string|DataFlowNode $row): DataFlowNode
    {
        if ($row instanceof DataFlowNode) {
            return $row;
        }

        [$unspecialized_id, $specialization_key] = self::splitIfSpecialized($id, $row);

        return DataFlowNode::fromRow(
            $id,
            $unspecialized_id,
            $specialization_key,
            $this->getLabel($row),
            $this->getLocation($row),
            $this->getTaints($row),
        );
    }

    /**
     * @return array{?string, ?string}
     * @psalm-pure
     */
    private static function splitIfSpecialized(string $id, string $row): array
    {
        if ((self::flags($row) & self::SPECIALIZED) === 0) {
            return [null, null];
        }

        $pos = (int) strpos($id, TaintFlowGraph::SPECIALIZATION_SEPARATOR);

        return [substr($id, 0, $pos), substr($id, $pos + strlen(TaintFlowGraph::SPECIALIZATION_SEPARATOR))];
    }

    /**
     * @psalm-pure
     */
    private static function flags(string $row): int
    {
        /** @var array{1: int} $values */
        $values = unpack('C', $row);

        return $values[1];
    }

    /**
     * @psalm-pure
     */
    private static function int32(string $row, int $offset): int
    {
        /** @var array{1: int} $values */
        $values = unpack('l', $row, $offset);

        return $values[1];
    }

    /**
     * @psalm-pure
     */
    private static function uint32(string $row, int $offset): int
    {
        /** @var array{1: int} $values */
        $values = unpack('V', $row, $offset);

        return $values[1];
    }

    /**
     * @psalm-mutation-free
     */
    public function getLabel(string|DataFlowNode $row): string
    {
        return $row instanceof DataFlowNode ? $row->label : $this->strings[self::uint32($row, self::LABEL_OFFSET)];
    }

    /**
     * @psalm-mutation-free
     */
    public function getTaints(string|DataFlowNode $row): int
    {
        if ($row instanceof DataFlowNode) {
            return $row->taints;
        }

        $flags = self::flags($row);

        if (($flags & self::TAINTED) === 0) {
            return 0;
        }

        /** @var array{1: int} $values */
        $values = unpack('q', $row, strlen($row) - 8);

        return $values[1];
    }

    /**
     * @psalm-mutation-free
     */
    public function hasLocation(string|DataFlowNode $row): bool
    {
        return $row instanceof DataFlowNode
            ? $row->code_location !== null
            : (self::flags($row) & self::LOCATED) !== 0;
    }

    /**
     * @psalm-mutation-free
     */
    public function getFilePath(string|DataFlowNode $row): ?string
    {
        if ($row instanceof DataFlowNode) {
            return $row->code_location?->file_path;
        }

        return (self::flags($row) & self::LOCATED) === 0
            ? null
            : $this->file_paths[self::uint32($row, self::FILE_OFFSET)];
    }

    /**
     * The raw start and end offsets of the location of the node of a row with a location (see hasLocation())
     *
     * @return array{int, int}
     * @psalm-mutation-free
     */
    public function getRawBounds(string|DataFlowNode $row): array
    {
        if ($row instanceof DataFlowNode) {
            return $row->code_location === null
                ? [0, 0]
                : [$row->code_location->raw_file_start, $row->code_location->raw_file_end];
        }

        return [self::int32($row, self::START_OFFSET), self::int32($row, self::END_OFFSET)];
    }

    /**
     * @psalm-mutation-free
     */
    public function getLocation(string|DataFlowNode $row): ?CodeLocation
    {
        if ($row instanceof DataFlowNode) {
            return $row->code_location;
        }

        $flags = self::flags($row);

        if (($flags & self::LOCATED) === 0) {
            return null;
        }

        $file = self::uint32($row, self::FILE_OFFSET);

        $state = ($flags & self::STATEFUL) === 0
            ? []
            : self::decodeState($this->strings[self::uint32($row, self::STATE_OFFSET)]);

        return CodeLocation::fromCompactState(
            $this->file_paths[$file],
            $this->file_names[$file],
            self::int32($row, self::LINE_OFFSET),
            self::int32($row, self::START_OFFSET),
            self::int32($row, self::END_OFFSET),
            $state,
        );
    }

    /**
     * The state of a location (see CodeLocation::getCompactState()) as a string: for each property, the length of
     * its name, its name, the type of its value (b, i, n or s) and, but for null, the length of its value as a
     * string and that string
     *
     * @param array<string, scalar|null> $state
     * @psalm-pure
     */
    private static function encodeState(array $state): string
    {
        $encoded = '';

        foreach ($state as $name => $value) {
            $encoded .= pack('V', strlen($name)) . $name;

            if ($value === null) {
                $encoded .= 'n';

                continue;
            }

            $type = match (true) {
                is_bool($value) => 'b',
                is_int($value) => 'i',
                default => 's',
            };
            $value = (string) $value;
            $encoded .= $type . pack('V', strlen($value)) . $value;
        }

        return $encoded;
    }

    /**
     * @return array<string, scalar|null>
     * @psalm-pure
     */
    private static function decodeState(string $encoded): array
    {
        $state = [];
        $length = strlen($encoded);

        for ($offset = 0; $offset < $length;) {
            $name_length = self::uint32($encoded, $offset);
            $name = substr($encoded, $offset + 4, $name_length);
            $offset += 4 + $name_length;
            $type = $encoded[$offset];
            $offset++;

            if ($type === 'n') {
                $state[$name] = null;

                continue;
            }

            $value_length = self::uint32($encoded, $offset);
            $value = substr($encoded, $offset + 4, $value_length);
            $offset += 4 + $value_length;

            $state[$name] = match ($type) {
                'b' => $value === '1',
                'i' => (int) $value,
                default => $value,
            };
        }

        return $state;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUnspecializedId(string $id, string|DataFlowNode $row): ?string
    {
        if ($row instanceof DataFlowNode) {
            return $row->unspecialized_id;
        }

        return self::splitIfSpecialized($id, $row)[0];
    }

    /**
     * @psalm-mutation-free
     */
    public function getSpecializationKey(string $id, string|DataFlowNode $row): ?string
    {
        if ($row instanceof DataFlowNode) {
            return $row->specialization_key;
        }

        return self::splitIfSpecialized($id, $row)[1];
    }

    /**
     * The row of the node of id $id with the label, location and taints of the node of $row, unspecialized (see
     * DataFlowNode::withSpecialization())
     *
     * @psalm-pure
     */
    public function withoutSpecialization(string $id, string|DataFlowNode $row): string|DataFlowNode
    {
        if ($row instanceof DataFlowNode) {
            return $row->withSpecialization($id, null, null, null);
        }

        $flags = self::flags($row);

        return ($flags & self::SPECIALIZED) === 0 ? $row : pack('C', $flags & ~self::SPECIALIZED) . substr($row, 1);
    }

    /**
     * The row of DataFlowNode::getForPathVariant(): the label and location of the node the path comes from, if
     * known, with no specialization nor taints
     *
     * @psalm-external-mutation-free
     */
    public function getPathVariant(
        string $from_id,
        string|DataFlowNode|null $from,
        string $path_type,
    ): string|DataFlowNode {
        if ($from === null) {
            return pack('CV', 0, $this->intern($from_id));
        }

        if ($from instanceof DataFlowNode) {
            return $this->encode(DataFlowNode::getForPathVariant($from_id, $from, $path_type));
        }

        $flags = self::flags($from);
        $row = pack('C', $flags & (self::LOCATED | self::STATEFUL)) . substr($from, 1);

        return ($flags & self::TAINTED) === 0 ? $row : substr($row, 0, -8);
    }

    /**
     * The maps of the indexes of the strings and files of $other to those of this table, adding those it lacks
     *
     * @return array{list<int>, list<int>}
     * @psalm-external-mutation-free
     */
    public function importTables(self $other): array
    {
        $strings = [];

        foreach ($other->strings as $string) {
            $strings[] = $this->intern($string);
        }

        $files = [];

        foreach ($other->file_paths as $index => $file_path) {
            $files[] = $this->internFile($file_path, $other->file_names[$index]);
        }

        return [$strings, $files];
    }

    /**
     * The row $row of another table, with the maps importTables() returned for it
     *
     * @param list<int> $strings
     * @param list<int> $files
     * @psalm-pure
     */
    public static function import(string|DataFlowNode $row, array $strings, array $files): string|DataFlowNode
    {
        if ($row instanceof DataFlowNode) {
            return $row;
        }

        $flags = self::flags($row);
        $imported = pack('CV', $flags, $strings[self::uint32($row, self::LABEL_OFFSET)]);
        $rest = self::FILE_OFFSET;

        if (($flags & self::LOCATED) !== 0) {
            $imported .= pack('V', $files[self::uint32($row, self::FILE_OFFSET)])
                . substr($row, self::START_OFFSET, 12);
            $rest = self::STATE_OFFSET;

            if (($flags & self::STATEFUL) !== 0) {
                $imported .= pack('V', $strings[self::uint32($row, self::STATE_OFFSET)]);
                $rest += 4;
            }
        }

        return $imported . substr($row, $rest);
    }
}
