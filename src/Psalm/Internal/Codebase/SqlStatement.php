<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use function array_key_last;
use function array_merge;
use function array_shift;
use function array_slice;
use function count;
use function ctype_digit;
use function in_array;
use function preg_match;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * What an SQL query writes into the database and reads from it, by table and column: the values an INSERT, REPLACE or
 * UPDATE stores in each column, and the columns of the rows a SELECT gives. A value is made of the parts of the query
 * the analysis doesn't know the text of (holes, numbered in the order they appear in the text, where the text has
 * HOLE_MARKER, the number and HOLE_MARKER again), of its placeholders (`?`, numbered from 0, `:name` and `$1`, named
 * without `:` and numbered from 0), and of the columns it copies.
 *
 * A table or column the query doesn't name (`INSERT INTO t VALUES (...)`, `SELECT *`, a name with a hole) is null:
 * any of them. Tables, and the columns of values, are lowercase; the column of a write and the keys of a read are
 * named as written. A query parse() doesn't model gives null.
 *
 * The keys of a read are those of the rows fetched it is under: its name and its position, or any key. A read of
 * `all_columns` (`*`, `t.*`) gives each column of the tables of its value under the column's name.
 *
 * @psalm-type Column = array{?string, ?string}
 * @psalm-type Value = array{holes: list<int>, placeholders: list<int|string>, columns: list<Column>}
 * @psalm-type Write = array{table: ?string, column: ?string, value: Value}
 * @psalm-type Read = array{keys: list<int|string>, any_key: bool, all_columns: bool, value: Value}
 * @psalm-type Token = array{0: string, 1: string, 2: list<int>, 3?: bool}
 * @psalm-capabilities read-props|write-this-props|write-refs
 * @internal
 */
final class SqlStatement
{
    public const HOLE_MARKER = "\x00";

    private const NO_VALUE = ['holes' => [], 'placeholders' => [], 'columns' => []];

    /** The words that can't be the alias of a table or of a selected expression */
    private const KEYWORDS = [
        'all', 'and', 'as', 'asc', 'between', 'by', 'case', 'cross', 'desc', 'distinct', 'else', 'end', 'except',
        'exists', 'for', 'from', 'full', 'group', 'having', 'in', 'inner', 'intersect', 'into', 'is', 'join', 'left',
        'like', 'limit', 'natural', 'not', 'null', 'offset', 'on', 'or', 'order', 'outer', 'returning', 'right',
        'select',
        'set', 'straight_join', 'then', 'union', 'using', 'values', 'when', 'where', 'window', 'with', 'lock', 'fetch',
        'top', 'ilike', 'regexp', 'rlike', 'escape', 'collate', 'div', 'mod', 'xor', 'true', 'false', 'unknown',
        'interval', 'binary', 'over', 'partition', 'default',
    ];

    /** The words that end the list of the tables of a SELECT or an UPDATE */
    private const FROM_ENDS = [
        'where', 'group', 'having', 'order', 'limit', 'offset', 'union', 'intersect', 'except', 'for', 'lock',
        'window', 'fetch', 'returning', 'set', 'into', 'procedure',
    ];

    /** @var list<Token> */
    private array $tokens;

    private int $position = 0;

    private int $placeholder_count = 0;

    /**
     * @param list<Token> $tokens
     */
    private function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * The writes and reads of the statements of $sql, or null if a statement isn't one parse() models
     *
     * @return array{writes: list<Write>, reads: list<Read>}|null
     * @psalm-pure
     */
    public static function parse(string $sql): ?array
    {
        $tokens = self::tokenize($sql);

        if ($tokens === null) {
            return null;
        }

        $writes = [];
        $reads = [];
        $statement = [];

        // the placeholders are numbered across the statements
        $parser = new self([]);

        foreach ([...$tokens, [';', ';', []]] as $token) {
            if ($token[0] !== ';') {
                $statement[] = $token;
                continue;
            }

            if ($statement === []) {
                continue;
            }

            $parser->tokens = $statement;
            $parser->position = 0;
            $result = $parser->parseStatement();
            $statement = [];

            if ($result === null) {
                return null;
            }

            $writes = array_merge($writes, $result['writes']);
            // what a query gives is what its last statement selects
            if ($result['reads'] !== []) {
                $reads = $result['reads'];
            }
        }

        return ['writes' => $writes, 'reads' => $reads];
    }

    /**
     * @return array{writes: list<Write>, reads: list<Read>}|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseStatement(): ?array
    {
        $first = $this->peekWord();

        if ($first === 'select' || (($this->tokens[0][0] ?? '') === '(' && $this->containsWordAtDepth('select', 1))) {
            $reads = $this->parseSelect($this->tokens);

            return $reads === null ? null : ['writes' => [], 'reads' => $reads];
        }

        if ($first === 'insert' || $first === 'replace') {
            return $this->parseInsert();
        }

        if ($first === 'update') {
            return $this->parseUpdate();
        }

        // what can't store values (DELETE, DDL, transactions, ...): a hole or a placeholder isn't written anywhere
        if (in_array($first, [
            'delete', 'truncate', 'create', 'drop', 'alter', 'begin', 'start', 'commit', 'rollback', 'savepoint',
            'release', 'set', 'lock', 'unlock', 'use', 'show', 'describe', 'desc', 'explain', 'analyze', 'vacuum',
            'pragma', 'grant', 'revoke', 'optimize', 'flush', 'kill',
        ], true)) {
            // but the rows a statement gives back with RETURNING aren't modelled
            return $this->containsWordAtDepth('returning', 0) ? null : ['writes' => [], 'reads' => []];
        }

        return null;
    }

    /**
     * @return array{writes: list<Write>, reads: list<Read>}|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseInsert(): ?array
    {
        $this->position++;

        while (in_array($this->peekWord(), ['low_priority', 'delayed', 'high_priority', 'ignore', 'or'], true)) {
            $this->position++;
            // INSERT OR REPLACE / OR IGNORE (SQLite)
            if (in_array($this->peekWord(), ['replace', 'ignore', 'rollback', 'abort', 'fail'], true)) {
                $this->position++;
            }
        }

        if ($this->peekWord() === 'into') {
            $this->position++;
        }

        $table = $this->parseTableName();

        if ($table === false) {
            return null;
        }

        [$table_name] = $table;

        // an alias (PostgreSQL)
        if ($this->peekWord() === 'as') {
            $this->position += 2;
        }

        $columns = null;

        if ($this->peek() === '(' && !$this->isSelectAhead()) {
            $columns = [];
            foreach (self::splitList($this->takeParenthesized()) as $column_tokens) {
                $column = self::getColumnName($column_tokens);

                if ($column === false) {
                    return null;
                }

                $columns[] = $column;
            }
        }

        $writes = [];
        $word = $this->peekWord();

        if ($word === 'values' || $word === 'value') {
            $this->position++;

            while ($this->peek() === '(') {
                foreach (self::splitList($this->takeParenthesized()) as $offset => $value_tokens) {
                    $writes[] = [
                        'table' => $table_name,
                        'column' => $columns === null ? null : ($columns[$offset] ?? null),
                        'value' => $this->getValue($value_tokens, [$table_name]),
                    ];
                }

                if ($this->peek() !== ',') {
                    break;
                }

                $this->position++;
            }
        } elseif ($word === 'set') {
            $this->position++;
            $assignments = $this->parseAssignments([$table_name], $table_name);

            if ($assignments === null) {
                return null;
            }

            $writes = $assignments;
        } elseif ($word === 'select' || $this->peek() === '(') {
            // INSERT ... SELECT copies the columns it selects
            $select = $this->takeUntilWord(['on', 'returning'], 0);
            $reads = $this->parseSelect($select);

            if ($reads === null) {
                return null;
            }

            foreach ($reads as $offset => $read) {
                $writes[] = [
                    'table' => $table_name,
                    'column' => $columns === null ? null : ($columns[$offset] ?? null),
                    'value' => $read['value'],
                ];
            }
        } elseif ($word === 'default') {
            $this->position += 2;
        } else {
            return null;
        }

        // ON DUPLICATE KEY UPDATE (MySQL), ON CONFLICT ... DO UPDATE SET (PostgreSQL, SQLite)
        if ($this->peekWord() === 'on') {
            $this->countPlaceholders($this->takeUntilWord(['update', 'returning'], 0));

            if ($this->peekWord() === 'update') {
                $this->position++;
                if ($this->peekWord() === 'set') {
                    $this->position++;
                }

                $tables = ['excluded' => $table_name];
                if ($table_name !== null) {
                    $tables[$table_name] = $table_name;
                }

                $assignments = $this->parseAssignments($tables, $table_name);

                if ($assignments === null) {
                    return null;
                }

                $writes = [...$writes, ...$assignments];
                // the condition of the update
                $this->countPlaceholders($this->takeUntilWord(['returning'], 0));
            }
        }

        $reads = $this->parseReturning($table_name === null ? [null] : [$table_name => $table_name]);

        if ($reads === null) {
            return null;
        }

        return ['writes' => $writes, 'reads' => $reads];
    }

    /**
     * The columns of RETURNING, which ends the statement
     *
     * @param array<array-key, ?string> $tables
     * @return list<Read>|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseReturning(array $tables): ?array
    {
        $reads = [];

        if ($this->peekWord() === 'returning') {
            $this->position++;
            $items = array_slice($this->tokens, $this->position);
            $this->position = count($this->tokens);

            foreach (self::splitList($items) as $offset => $item) {
                $read = $this->getRead($item, $tables, $offset);

                if ($read === null) {
                    return null;
                }

                $reads[] = $read;
            }
        }

        return $this->position < count($this->tokens) ? null : $reads;
    }

    /**
     * @return array{writes: list<Write>, reads: list<Read>}|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseUpdate(): ?array
    {
        $this->position++;

        while (in_array($this->peekWord(), ['low_priority', 'ignore', 'only', 'or'], true)) {
            $this->position++;
            if (in_array($this->peekWord(), ['replace', 'ignore', 'rollback', 'abort', 'fail'], true)) {
                $this->position++;
            }
        }

        $from_tokens = $this->takeUntilWord(['set'], 0);
        $tables = $this->parseFrom($from_tokens);

        if ($tables === null || $this->peekWord() !== 'set') {
            return null;
        }

        $this->countPlaceholders($from_tokens);
        $this->position++;

        $distinct_tables = self::getAllTablesColumns($tables, null);
        $default_table = count($distinct_tables) === 1 ? $distinct_tables[0][0] : null;
        $assignments = $this->parseAssignments($tables, $default_table);

        if ($assignments === null) {
            return null;
        }

        // FROM (PostgreSQL), WHERE, ORDER BY, LIMIT: only placeholders to number
        $this->countPlaceholders($this->takeUntilWord(['returning'], 0));
        $reads = $this->parseReturning($tables);

        if ($reads === null) {
            return null;
        }

        return ['writes' => $assignments, 'reads' => $reads];
    }

    /**
     * The assignments of a SET clause, up to what ends it
     *
     * @param array<array-key, ?string> $tables the tables, by alias, the columns can be of
     * @return list<Write>|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseAssignments(array $tables, ?string $default_table): ?array
    {
        $assignments = $this->takeUntilWord(['where', 'from', 'order', 'limit', 'returning', 'on'], 0);
        $writes = [];

        foreach (self::splitList($assignments) as $assignment) {
            $equals = null;
            foreach ($assignment as $offset => $token) {
                if ($token[0] === '=') {
                    $equals = $offset;
                    break;
                }
            }

            if ($equals === null) {
                return null;
            }

            $target = array_slice($assignment, 0, $equals);
            $column = self::getColumnName($target);

            if ($column === false) {
                return null;
            }

            $table = $default_table;
            if (count($target) === 3 && $target[1][0] === '.') {
                $qualifier = self::getIdentifier($target[0]);
                $table = $qualifier === null ? null : ($tables[strtolower($qualifier)] ?? strtolower($qualifier));
            }

            $writes[] = [
                'table' => $table,
                'column' => $column,
                'value' => $this->getValue(array_slice($assignment, $equals + 1), $tables),
            ];
        }

        return $writes;
    }

    /**
     * The columns a SELECT (or several joined by UNION) gives, in order
     *
     * @param list<Token> $tokens
     * @return list<Read>|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseSelect(array $tokens): ?array
    {
        // a parenthesized SELECT
        while ($tokens !== [] && $tokens[0][0] === '('
            && self::getClosingParenthesis($tokens, 0) === count($tokens) - 1
        ) {
            $tokens = array_slice($tokens, 1, -1);
        }

        $parts = [[]];
        $depth = 0;
        foreach ($tokens as $token) {
            if ($token[0] === '(') {
                $depth++;
            } elseif ($token[0] === ')') {
                $depth--;
            } elseif ($depth === 0 && $token[0] === 'word'
                && in_array(strtolower($token[1]), ['union', 'intersect', 'except'], true)
            ) {
                $parts[] = [];
                continue;
            }

            $parts[array_key_last($parts)][] = $token;
        }

        $parts_reads = [];
        foreach ($parts as $part) {
            // UNION ALL, UNION DISTINCT
            while ($part !== [] && $part[0][0] === 'word'
                && in_array(strtolower($part[0][1]), ['all', 'distinct'], true)
            ) {
                $part = array_slice($part, 1);
            }

            $part_reads = $this->parseSimpleSelect($part);

            if ($part_reads === null) {
                return null;
            }

            $parts_reads[] = $part_reads;
        }

        // the rows of the other parts are named after the columns of the first
        $reads = array_shift($parts_reads);
        foreach ($parts_reads as $part_reads) {
            if (count($part_reads) !== count($reads)) {
                return null;
            }

            foreach ($reads as $offset => $read) {
                // the columns of the other parts are under the names of those of the first, by position
                $reads[$offset] = [
                    'keys' => $read['keys'],
                    'any_key' => $read['any_key'] || $read['all_columns'],
                    'all_columns' => false,
                    'value' => self::mergeValues($read['value'], $part_reads[$offset]['value'] ?? self::NO_VALUE),
                ];
            }
        }

        return $reads;
    }

    /**
     * @param list<Token> $tokens
     * @return list<Read>|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseSimpleSelect(array $tokens): ?array
    {
        while ($tokens !== [] && $tokens[0][0] === '('
            && self::getClosingParenthesis($tokens, 0) === count($tokens) - 1
        ) {
            $tokens = array_slice($tokens, 1, -1);
        }

        if ($tokens === [] || $tokens[0][0] !== 'word' || strtolower($tokens[0][1]) !== 'select') {
            return null;
        }

        $parser = new self($tokens);
        $parser->placeholder_count = $this->placeholder_count;
        $parser->position = 1;

        while (in_array($parser->peekWord(), ['distinct', 'all', 'distinctrow', 'high_priority', 'straight_join',
            'sql_no_cache', 'sql_cache', 'sql_calc_found_rows', 'sql_small_result', 'sql_big_result',
            'sql_buffer_result'], true)
        ) {
            $parser->position++;
            // DISTINCT ON (...) (PostgreSQL)
            if ($parser->peekWord() === 'on' && ($parser->tokens[$parser->position + 1][0] ?? '') === '(') {
                $parser->position++;
                $this->countPlaceholders($parser->takeParenthesized());
            }
        }

        // TOP n (SQL Server)
        if ($parser->peekWord() === 'top') {
            $parser->position += 2;
        }

        $items = $parser->takeUntilWord(['from', 'into', 'where', 'group', 'having', 'order', 'limit', 'window'], 0);
        $from_tokens = [];
        if ($parser->peekWord() === 'from') {
            $parser->position++;
            $from_tokens = $parser->takeUntilWord(self::FROM_ENDS, 0);
        }

        $tables = $parser->parseFrom($from_tokens);

        if ($tables === null) {
            return null;
        }

        // the placeholders the items have come before those of the tables and of what follows
        $item_parser = new self($items);
        $item_parser->placeholder_count = $this->placeholder_count;

        $reads = [];
        $after_star = false;
        foreach (self::splitList($items) as $offset => $item) {
            $read = $item_parser->getRead($item, $tables, $after_star ? null : $offset);

            if ($read === null) {
                return null;
            }

            if ($read['value']['columns'] !== [] && $read['value']['columns'][0][1] === null && $read['keys'] === []) {
                $after_star = true;
            }

            $reads[] = $read;
        }

        $this->placeholder_count = $item_parser->placeholder_count;
        $this->countPlaceholders($from_tokens);
        $this->countPlaceholders(array_slice($parser->tokens, $parser->position));

        return $reads;
    }

    /**
     * What a selected item gives, under its name and its position
     *
     * @param list<Token> $item
     * @param array<array-key, ?string> $tables
     * @return Read|null
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function getRead(array $item, array $tables, ?int $position): ?array
    {
        if ($item === []) {
            return null;
        }

        $keys = [];
        $alias = null;
        $count = count($item);

        // `expression AS alias`, `expression alias`
        if ($count >= 2) {
            $last = self::getIdentifier($item[$count - 1]);
            $before = $item[$count - 2];

            if ($last !== null && $before[0] === 'word' && strtolower($before[1]) === 'as') {
                $alias = $item[$count - 1][1];
                $item = array_slice($item, 0, -2);
            } elseif ($last !== null
                && !in_array(strtolower($item[$count - 1][1]), self::KEYWORDS, true)
                && $before[0] !== '.'
                && ($before[0] === ')'
                    || self::getIdentifier($before) !== null
                    || in_array($before[0], ['string', 'number'], true))
                && !($item[$count - 1][0] === 'word' && $before[0] === 'word'
                    && in_array(strtolower($before[1]), self::KEYWORDS, true))
            ) {
                $alias = $item[$count - 1][1];
                $item = array_slice($item, 0, -1);
            }
        }

        // `*` and `t.*`
        if (count($item) === 1 && $item[0][0] === '*' && $alias === null) {
            return [
                'keys' => [],
                'any_key' => false,
                'all_columns' => true,
                'value' => ['holes' => [], 'placeholders' => [], 'columns' => self::getAllTablesColumns($tables, null)],
            ];
        }

        if (count($item) === 3 && $item[1][0] === '.' && $item[2][0] === '*' && $alias === null) {
            $qualifier = self::getIdentifier($item[0]);
            $table = $qualifier === null ? null : ($tables[strtolower($qualifier)] ?? strtolower($qualifier));

            return [
                'keys' => [],
                'any_key' => false,
                'all_columns' => true,
                'value' => ['holes' => [], 'placeholders' => [], 'columns' => [[$table, null]]],
            ];
        }

        $value = $this->getValue($item, $tables);

        $name = $alias !== null ? self::unquote($alias) : self::getColumnName($item);
        if ($name !== false && $name !== null) {
            $keys[] = $name;
        }

        if ($position !== null) {
            $keys[] = $position;
        }

        return [
            'keys' => $keys,
            // the name PDO gives an expression without an alias is its text, which isn't modelled
            'any_key' => $keys === [] || ($alias === null && self::getColumnName($item) === false),
            'all_columns' => false,
            'value' => $value,
        ];
    }

    /**
     * The tables of a FROM clause (or of the start of an UPDATE), by alias and by name
     *
     * @param list<Token> $tokens
     * @return array<array-key, ?string>|null
     * @psalm-pure
     */
    private function parseFrom(array $tokens): ?array
    {
        $tables = [];
        $offset = 0;
        $count = count($tokens);

        while ($offset < $count) {
            $token = $tokens[$offset];

            if ($token[0] === ',' || ($token[0] === 'word' && in_array(strtolower($token[1]), [
                'join', 'inner', 'left', 'right', 'full', 'outer', 'cross', 'natural', 'straight_join', 'lateral',
            ], true))) {
                $offset++;
                continue;
            }

            if ($token[0] === 'word' && in_array(strtolower($token[1]), ['on', 'using'], true)) {
                // the condition of the join: up to the next table
                $offset++;
                $depth = 0;
                while ($offset < $count) {
                    $next = $tokens[$offset];
                    if ($next[0] === '(') {
                        $depth++;
                    } elseif ($next[0] === ')') {
                        $depth--;
                    } elseif ($depth === 0 && ($next[0] === ',' || ($next[0] === 'word'
                        && in_array(strtolower($next[1]), ['join', 'inner', 'left', 'right', 'full', 'cross',
                            'natural', 'straight_join'], true)))
                    ) {
                        break;
                    }

                    $offset++;
                }

                continue;
            }

            if ($token[0] === '(') {
                // a derived table: what it selects isn't followed, so its columns can be any
                $closing = self::getClosingParenthesis($tokens, $offset);

                if ($closing === null) {
                    return null;
                }

                $table = null;
                $offset = $closing + 1;
            } else {
                $parser = new self(array_slice($tokens, $offset));
                $name = $parser->parseTableName();

                if ($name === false) {
                    return null;
                }

                [$table] = $name;
                $offset += $parser->position;

                if ($table !== null) {
                    $tables[$table] = $table;
                }
            }

            // the alias
            if (isset($tokens[$offset]) && $tokens[$offset][0] === 'word' && strtolower($tokens[$offset][1]) === 'as') {
                $offset++;
            }

            if (isset($tokens[$offset])
                && ($alias = self::getIdentifier($tokens[$offset])) !== null
                && !in_array(strtolower($tokens[$offset][1]), self::KEYWORDS, true)
                && !in_array(strtolower($tokens[$offset][1]), ['lateral', 'force', 'ignore', 'use'], true)
            ) {
                $tables[strtolower($alias)] = $table;
                $offset++;
            } elseif ($table === null) {
                $tables[] = null;
            }

            // index hints (MySQL): USE INDEX (...), FORCE INDEX (...)
            while (isset($tokens[$offset]) && $tokens[$offset][0] === 'word'
                && in_array(strtolower($tokens[$offset][1]), ['use', 'force', 'ignore'], true)
            ) {
                $offset += 2;
                if (isset($tokens[$offset]) && $tokens[$offset][0] === 'word'
                    && strtolower($tokens[$offset][1]) === 'for'
                ) {
                    $offset += 2;
                    if (isset($tokens[$offset]) && $tokens[$offset][0] === 'word') {
                        $offset++;
                    }
                }
                $closing = isset($tokens[$offset]) ? self::getClosingParenthesis($tokens, $offset) : null;
                if ($closing === null) {
                    return null;
                }
                $offset = $closing + 1;
            }
        }

        return $tables;
    }

    /**
     * The name of a table (without its schema), null when the query doesn't say which, and false if what follows isn't
     * a name
     *
     * @return array{?string}|false
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function parseTableName(): array|false
    {
        $token = $this->tokens[$this->position] ?? null;

        if ($token === null) {
            return false;
        }

        if ($token[0] === 'hole') {
            $this->position++;
            // `$prefix . 'users'`
            while (isset($this->tokens[$this->position])
                && in_array($this->tokens[$this->position][0], ['word', 'ident', 'hole'], true)
                && $this->tokens[$this->position][2] === []
                && !in_array(strtolower($this->tokens[$this->position][1]), self::KEYWORDS, true)
                && $this->isGlued($this->position)
            ) {
                $this->position++;
            }

            return [null];
        }

        $name = self::getIdentifier($token);

        if ($name === null || in_array(strtolower($token[1]), ['select', 'values', 'set'], true)) {
            return false;
        }

        $this->position++;
        $holes = $token[2] !== [];

        // schema.table
        while (($this->tokens[$this->position][0] ?? '') === '.') {
            $next = $this->tokens[$this->position + 1] ?? null;
            $next_name = $next === null ? null : self::getIdentifier($next);

            if ($next === null || ($next_name === null && $next[0] !== 'hole')) {
                return false;
            }

            $this->position += 2;
            $holes = $holes || $next[0] === 'hole' || $next[2] !== [];
            $name = $next_name;
        }

        // `'users_' . $suffix`
        while (isset($this->tokens[$this->position]) && $this->isGlued($this->position)
            && in_array($this->tokens[$this->position][0], ['word', 'ident', 'hole'], true)
        ) {
            $holes = true;
            $this->position++;
        }

        return [$holes || $name === null ? null : strtolower($name)];
    }

    /**
     * The holes, placeholders and columns of the expression
     *
     * @param list<Token> $tokens
     * @param array<array-key, ?string> $tables the tables, by alias, a column without a table can be of
     * @return Value
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function getValue(array $tokens, array $tables): array
    {
        $holes = [];
        $placeholders = [];
        $columns = [];
        $has_subquery = false;

        foreach ($tokens as $offset => $token) {
            if ($token[0] === 'word' && strtolower($token[1]) === 'select') {
                $has_subquery = true;
            }

            $holes = [...$holes, ...$token[2]];

            if ($token[0] === 'hole') {
                $holes[] = (int) $token[1];
            } elseif ($token[0] === 'placeholder') {
                $placeholders[] = $this->getPlaceholder($token[1]);
            } elseif (($token[0] === 'word' || $token[0] === 'ident')
                && ($tokens[$offset - 1][0] ?? '') !== '.'
                // the type of a cast (`x::text`)
                && ($tokens[$offset - 1][0] ?? '') !== ':'
                && ($tokens[$offset + 1][0] ?? '') !== '('
                && !($token[0] === 'word' && in_array(strtolower($token[1]), self::KEYWORDS, true))
            ) {
                // `t.c` or `c`
                if (($tokens[$offset + 1][0] ?? '') === '.') {
                    $column_token = $tokens[$offset + 2] ?? null;
                    if ($column_token === null || $column_token[0] === '*') {
                        $column = null;
                    } else {
                        $column = self::getIdentifier($column_token);
                        if ($column === null) {
                            continue;
                        }
                    }

                    $qualifier = strtolower(self::unquote($token[1]));
                    $table = $tables[$qualifier] ?? $qualifier;
                    $columns[] = [$table, $column === null ? null : strtolower($column)];
                } elseif ($token[0] === 'ident' || !self::isFunctionLikeKeyword($token[1])) {
                    foreach (self::getAllTablesColumns($tables, strtolower(self::unquote($token[1]))) as $column) {
                        $columns[] = $column;
                    }
                }
            }
        }

        // what a subquery selects isn't followed: any column
        return [
            'holes' => $holes,
            'placeholders' => $placeholders,
            'columns' => $has_subquery ? [[null, null]] : $columns,
        ];
    }

    /**
     * @param array<array-key, ?string> $tables
     * @return list<Column>
     * @psalm-pure
     */
    private static function getAllTablesColumns(array $tables, ?string $column): array
    {
        $columns = [];
        $seen = [];

        foreach ($tables as $table) {
            if (isset($seen[(string) $table])) {
                continue;
            }

            $seen[(string) $table] = true;
            $columns[] = [$table, $column];
        }

        return $columns === [] ? [[null, $column]] : $columns;
    }

    /**
     * Words of expressions that name no column
     *
     * @psalm-pure
     */
    private static function isFunctionLikeKeyword(string $word): bool
    {
        return in_array(strtolower($word), [
            'current_date', 'current_time', 'current_timestamp', 'current_user', 'localtime', 'localtimestamp',
            'session_user', 'user', 'now', 'sysdate', 'utc_timestamp', 'utc_date', 'utc_time',
        ], true);
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function getPlaceholder(string $text): int|string
    {
        if ($text === '?') {
            return $this->placeholder_count++;
        }

        if ($text[0] === '$') {
            return (int) substr($text, 1) - 1;
        }

        return substr($text, 1);
    }

    /**
     * Numbers the positional placeholders of tokens whose values aren't modelled
     *
     * @param list<Token> $tokens
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function countPlaceholders(array $tokens): void
    {
        foreach ($tokens as $token) {
            if ($token[0] === 'placeholder') {
                $this->getPlaceholder($token[1]);
            }
        }
    }

    /**
     * The name of a column (`c`, `t.c`), null when the query doesn't say which, and false for anything else
     *
     * @param list<Token> $tokens
     * @psalm-pure
     */
    private static function getColumnName(array $tokens): string|null|false
    {
        if (count($tokens) === 3 && isset($tokens[1], $tokens[2]) && $tokens[1][0] === '.') {
            $tokens = [$tokens[2]];
        }

        if (count($tokens) !== 1 || !isset($tokens[0])) {
            return false;
        }

        $token = $tokens[0];

        if ($token[0] === 'hole') {
            return null;
        }

        $name = self::getIdentifier($token);

        if ($name === null) {
            return false;
        }

        return $token[2] === [] ? $name : null;
    }

    /**
     * The name a word or a quoted identifier gives
     *
     * @param Token $token
     * @psalm-pure
     */
    private static function getIdentifier(array $token): ?string
    {
        return $token[0] === 'word' || $token[0] === 'ident' ? self::unquote($token[1]) : null;
    }

    /**
     * @psalm-pure
     */
    private static function unquote(string $name): string
    {
        if ($name !== '' && in_array($name[0], ['`', '"', '['], true)) {
            return substr($name, 1, -1);
        }

        return $name;
    }

    /**
     * @param Value ...$values
     * @return Value
     * @psalm-pure
     */
    private static function mergeValues(array ...$values): array
    {
        $merged = ['holes' => [], 'placeholders' => [], 'columns' => []];

        foreach ($values as $value) {
            $merged['holes'] = [...$merged['holes'], ...$value['holes']];
            $merged['placeholders'] = [...$merged['placeholders'], ...$value['placeholders']];
            $merged['columns'] = [...$merged['columns'], ...$value['columns']];
        }

        return $merged;
    }

    /**
     * Whether the token at $offset follows the one before it without a space (`users_` . $suffix)
     *
     * @psalm-capabilities read-props
     */
    private function isGlued(int $offset): bool
    {
        return ($this->tokens[$offset][3] ?? false) === true;
    }

    /**
     * @psalm-capabilities read-props
     */
    private function peek(): ?string
    {
        return $this->tokens[$this->position][0] ?? null;
    }

    /**
     * @psalm-capabilities read-props
     */
    private function peekWord(): ?string
    {
        $token = $this->tokens[$this->position] ?? null;

        return $token !== null && $token[0] === 'word' ? strtolower($token[1]) : null;
    }

    /**
     * @psalm-capabilities read-props
     */
    private function isSelectAhead(): bool
    {
        $offset = $this->position;

        while (($this->tokens[$offset][0] ?? '') === '(') {
            $offset++;
        }

        $token = $this->tokens[$offset] ?? null;

        return $token !== null && $token[0] === 'word' && in_array(strtolower($token[1]), ['select', 'with'], true);
    }

    /**
     * @psalm-capabilities read-props
     */
    private function containsWordAtDepth(string $word, int $at_depth): bool
    {
        $depth = 0;

        foreach (array_slice($this->tokens, $this->position) as $token) {
            if ($token[0] === '(') {
                $depth++;
            } elseif ($token[0] === ')') {
                $depth--;
            } elseif ($depth === $at_depth && $token[0] === 'word' && strtolower($token[1]) === $word) {
                return true;
            }
        }

        return false;
    }

    /**
     * The tokens inside the parentheses that start at the current position, after which it moves
     *
     * @return list<Token>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function takeParenthesized(): array
    {
        $closing = self::getClosingParenthesis($this->tokens, $this->position);

        if ($closing === null) {
            $tokens = array_slice($this->tokens, $this->position + 1);
            $this->position = count($this->tokens);

            return $tokens;
        }

        $tokens = array_slice($this->tokens, $this->position + 1, $closing - $this->position - 1);
        $this->position = $closing + 1;

        return $tokens;
    }

    /**
     * The tokens from the current position up to one of the words outside parentheses, after which it moves
     *
     * @param list<string> $words
     * @return list<Token>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function takeUntilWord(array $words, int $at_depth): array
    {
        $depth = 0;
        $start = $this->position;
        $count = count($this->tokens);

        while ($this->position < $count) {
            $token = $this->tokens[$this->position];

            if ($token[0] === '(') {
                $depth++;
            } elseif ($token[0] === ')') {
                $depth--;
            } elseif ($depth === $at_depth && $token[0] === 'word' && in_array(strtolower($token[1]), $words, true)) {
                break;
            }

            $this->position++;
        }

        return array_slice($this->tokens, $start, $this->position - $start);
    }

    /**
     * @param list<Token> $tokens
     * @psalm-pure
     */
    private static function getClosingParenthesis(array $tokens, int $opening): ?int
    {
        $depth = 0;
        $count = count($tokens);

        for ($offset = $opening; $offset < $count; $offset++) {
            if ($tokens[$offset][0] === '(') {
                $depth++;
            } elseif ($tokens[$offset][0] === ')') {
                $depth--;

                if ($depth === 0) {
                    return $offset;
                }
            }
        }

        return null;
    }

    /**
     * The items of a list separated by commas outside parentheses
     *
     * @param list<Token> $tokens
     * @return list<list<Token>>
     * @psalm-pure
     */
    private static function splitList(array $tokens): array
    {
        if ($tokens === []) {
            return [];
        }

        $items = [];
        $item = [];
        $depth = 0;

        foreach ($tokens as $token) {
            if ($token[0] === '(') {
                $depth++;
            } elseif ($token[0] === ')') {
                $depth--;
            } elseif ($token[0] === ',' && $depth === 0) {
                $items[] = $item;
                $item = [];
                continue;
            }

            $item[] = $token;
        }

        $items[] = $item;

        return $items;
    }


    /**
     * The tokens of the text: [type, text, the holes it contains] and, for the tokens that follow the previous one
     * without a space, true; the types are `word`, `ident` (a quoted identifier), `string`, `number`, `placeholder`,
     * `hole`, and the character of any other punctuation. Null if the text doesn't end a string or a comment it starts.
     *
     * @return list<Token>|null
     * @psalm-pure
     */
    private static function tokenize(string $sql): ?array
    {
        $tokens = [];
        $length = strlen($sql);
        $offset = 0;
        $glued = false;

        while ($offset < $length) {
            $char = $sql[$offset];

            if ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r" || $char === "\f") {
                $offset++;
                $glued = false;
                continue;
            }

            $start = $offset;

            // comments
            if (($char === '-' && ($sql[$offset + 1] ?? '') === '-') || $char === '#') {
                $end = strpos($sql, "\n", $offset);
                $offset = $end === false ? $length : $end + 1;
                $glued = false;
                continue;
            }

            if ($char === '/' && ($sql[$offset + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $offset + 2);
                if ($end === false) {
                    return null;
                }
                $offset = $end + 2;
                $glued = false;
                continue;
            }

            $holes = null;

            if ($char === self::HOLE_MARKER) {
                $end = strpos($sql, self::HOLE_MARKER, $offset + 1);
                if ($end === false) {
                    return null;
                }
                $type = 'hole';
                $text = substr($sql, $offset + 1, $end - $offset - 1);
                $holes = [];
                $offset = $end + 1;
            } elseif ($char === '\'' || $char === '"' || $char === '`') {
                $quoted = self::readQuoted($sql, $offset, $char);
                if ($quoted === null) {
                    return null;
                }
                [$text, $holes, $offset] = $quoted;
                // "..." is a string in MySQL: what it holds is its value, as for '...'
                $type = $char === '\'' || ($char === '"' && $holes !== []) ? 'string' : 'ident';
            } elseif ($char === '['
                && ($text = self::matchAt('/\G\[[A-Za-z_][A-Za-z0-9_ ]*\]/', $sql, $offset)) !== null
            ) {
                $type = 'ident';
                $offset += strlen($text);
            } elseif (($text = self::matchAt('/\G[A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*/', $sql, $offset))
                !== null
            ) {
                $type = 'word';
                $offset += strlen($text);
                // N'...', E'...', X'...', B'...', _utf8'...'
                if (($sql[$offset] ?? '') === '\'' && preg_match('/^(?:[nexb]|_\w+)$/i', $text)) {
                    $quoted = self::readQuoted($sql, $offset, '\'');
                    if ($quoted === null) {
                        return null;
                    }
                    [$text, $holes, $offset] = $quoted;
                    $type = 'string';
                }
            } elseif (($text = self::matchAt(
                '/\G(?:0x[0-9A-Fa-f]+|[0-9]+(?:\.[0-9]*)?(?:[eE][-+]?[0-9]+)?|\.[0-9]+)/',
                $sql,
                $offset,
            )) !== null) {
                $type = 'number';
                $offset += strlen($text);
            } elseif ($char === '?' && !in_array($sql[$offset + 1] ?? '', ['|', '&', '?'], true)) {
                $type = 'placeholder';
                $text = '?';
                $offset++;
            } elseif ($char === ':' && ($sql[$offset + 1] ?? '') !== ':' && ($offset === 0 || $sql[$offset - 1] !== ':')
                && ($text = self::matchAt('/\G:[A-Za-z_][A-Za-z0-9_]*/', $sql, $offset)) !== null
            ) {
                $type = 'placeholder';
                $offset += strlen($text);
            } elseif ($char === '$' && ($text = self::matchAt('/\G\$[1-9][0-9]*/', $sql, $offset)) !== null) {
                $type = 'placeholder';
                $offset += strlen($text);
            } elseif ($char === '$' && ($delimiter = self::matchAt('/\G\$[A-Za-z_]*\$/', $sql, $offset)) !== null) {
                // a dollar-quoted string (PostgreSQL)
                $end = strpos($sql, $delimiter, $offset + strlen($delimiter));
                if ($end === false) {
                    return null;
                }
                $type = 'string';
                $text = substr($sql, $offset, $end + strlen($delimiter) - $offset);
                $offset = $end + strlen($delimiter);
            } else {
                $type = $char;
                $text = $char;
                $offset++;
            }

            $tokens[] = [$type, $text, $holes ?? self::getHoles($text), $glued && $start > 0];
            $glued = true;
        }

        return $tokens;
    }

    /**
     * What the pattern, which starts with `\G`, matches at the offset
     *
     * @param non-empty-string $pattern
     * @psalm-pure
     */
    private static function matchAt(string $pattern, string $sql, int $offset): ?string
    {
        return preg_match($pattern, $sql, $matches, 0, $offset) === 1 ? $matches[0] ?? null : null;
    }

    /**
     * The text of the quoted string or identifier starting at $offset, the holes it holds, and the offset after it
     *
     * @return array{string, list<int>, int}|null
     * @psalm-pure
     */
    private static function readQuoted(string $sql, int $offset, string $quote): ?array
    {
        $length = strlen($sql);
        $position = $offset + 1;

        while ($position < $length) {
            $char = $sql[$position];

            if ($char === '\\' && $quote !== '`') {
                $position += 2;
                continue;
            }

            if ($char === $quote) {
                if (($sql[$position + 1] ?? '') === $quote) {
                    $position += 2;
                    continue;
                }

                $text = substr($sql, $offset, $position + 1 - $offset);

                return [$text, self::getHoles($text), $position + 1];
            }

            $position++;
        }

        return null;
    }

    /**
     * @return list<int>
     * @psalm-pure
     */
    private static function getHoles(string $text): array
    {
        $holes = [];
        $offset = 0;

        while (($start = strpos($text, self::HOLE_MARKER, $offset)) !== false) {
            $end = strpos($text, self::HOLE_MARKER, $start + 1);

            if ($end === false) {
                break;
            }

            $number = substr($text, $start + 1, $end - $start - 1);
            if (ctype_digit($number)) {
                $holes[] = (int) $number;
            }

            $offset = $end + 1;
        }

        return $holes;
    }
}
