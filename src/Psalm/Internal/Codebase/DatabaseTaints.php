<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function count;
use function dirname;
use function is_int;
use function ltrim;
use function preg_split;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * What the database holds: the values an INSERT, REPLACE or UPDATE stores in a column flow into what the SELECTs of
 * that column give, wherever they are (see SqlStatement). The database is a keyed store (see TaintStore) whose names
 * are `table.column`: a query the analysis can't parse, or whose table or column isn't known, writes or reads under
 * the names starting with what it knows (any name, `table.`), so that no value is lost. The rows of a table, each
 * column under its name, are another keyed store, whose names are the tables: `SELECT *` reads them.
 *
 * The calls that run queries are described by `@psalm-database-*` annotations (see
 * FunctionLikeStorage::$database_calls)
 * or, for the builtins, by dictionaries/InternalDatabaseMap.php, as lists of these operations:
 * - `query`: runs the SQL given to `sql`, with the values of the placeholders given to `params` if any; what the call
 *   returns holds the rows the query selects, as `result` says (see addResult());
 * - `prepare`: the same, but the values of the placeholders are given later to the statement the call returns, by
 *   `execute` (`params`) and `bind` (`value` under the placeholder `key`, `base` being the number of the first
 *   positional placeholder, or the values given from `values` on, in the order of the placeholders);
 * - `insert` and `update`: store the values of the array given to `data`, under its keys, in the columns of the table
 *   given to `table`;
 * - `fetch`: what the call returns is from the rows the result given to `from` (or the object the method is called on)
 *   holds, as `result` says.
 *
 * A prepared statement is named by a literal given to `name` (pgsql), or else is the statement the call returns, that
 * the variables it is assigned to keep (see recordAssignment()). The values given to a statement the analysis can't
 * tell are stored under any name.
 *
 * @psalm-type Spec = array{
 *     0: 'query'|'prepare'|'execute'|'bind'|'insert'|'update'|'select'|'fetch',
 *     sql?: int,
 *     params?: int,
 *     statement?: int,
 *     name?: int,
 *     key?: int,
 *     value?: int,
 *     values?: int,
 *     base?: int,
 *     table?: int,
 *     data?: int,
 *     from?: int,
 *     result?: 'result'|'rows'|'value'|'column'|'any_value'|'any_column'|'value_or_row'|'none',
 *     optional_first?: int,
 * }
 * @psalm-type Value = array{
 *     holes: list<int>,
 *     placeholders: list<int|string|null>,
 *     columns: list<array{?string, ?string}>,
 * }
 * @internal
 */
final class DatabaseTaints
{
    public const STORE = 'database';

    private const ROWS_STORE = 'database rows';

    private const RESULTS = ['result', 'rows', 'value', 'column', 'any_value', 'any_column', 'value_or_row', 'none'];

    /** @var null|array<lowercase-string, list<Spec>> */
    private static ?array $map = null;

    /**
     * The operations a call of the function-like makes: those of its annotations, else those of
     * dictionaries/InternalDatabaseMap.php for a builtin
     *
     * @return list<Spec>
     * @psalm-capabilities read-props|read-globals|write-globals
     */
    public static function getSpecs(string $function_id, ?FunctionLikeStorage $storage): array
    {
        if ($storage !== null && $storage->database_calls !== []) {
            return $storage->database_calls;
        }

        if (self::$map === null) {
            /** @var array<lowercase-string, list<Spec>> */
            self::$map = require(dirname(__DIR__, 4) . '/dictionaries/InternalDatabaseMap.php');
        }

        return self::$map[strtolower($function_id)] ?? [];
    }

    /**
     * Adds the operations of the `@psalm-database-*` annotations (kind, what follows it) to the storage, and returns
     * what is wrong with them
     *
     * @param list<array{string, string}> $annotations
     * @return list<string>
     * @psalm-capabilities read-props|write-props
     */
    public static function addAnnotations(FunctionLikeStorage $storage, array $annotations): array
    {
        $invalid = [];
        foreach ($annotations as [$kind, $text]) {
            $error = self::addAnnotation($storage, $kind, $text);
            if ($error !== null) {
                $invalid[] = $error;
            }
        }

        return $invalid;
    }

    /**
     * Adds the operation of a `@psalm-database-$kind` annotation to the storage, or says what is wrong with it:
     * - `@psalm-database-query $sql [$params] [result]`, `@psalm-database-prepare $sql`;
     * - `@psalm-database-execute [$params]` and `@psalm-database-bind $key $value` (a positional placeholder is
     *   numbered from 1) or `@psalm-database-bind ...$values`, for the statement the method is called on;
     * - `@psalm-database-insert $table $data`, `@psalm-database-update $table $data`, `@psalm-database-select $table`;
     * - `@psalm-database-fetch [$result] [result]`, from the object the method is called on if no parameter is named.
     *
     * `result` says what the call returns of the rows: `result` (the result itself, or a row), `rows` (a list of
     * rows), `value` (the first column of a row), `column` (a list of first columns), `none`.
     *
     * @psalm-capabilities read-props|write-props
     */
    private static function addAnnotation(FunctionLikeStorage $storage, string $kind, string $text): ?string
    {
        $words = $text === '' ? [] : preg_split('/\s+/', $text);
        $params = [];
        $variadic = false;
        $result = null;

        foreach ($words === false ? [] : $words as $word) {
            if ($word === '' || $word[0] === '*') {
                break;
            }

            if (str_starts_with($word, '...$')) {
                $variadic = true;
                $word = substr($word, 3);
            }

            if ($word[0] === '$') {
                $offset = null;
                foreach ($storage->params as $i => $param) {
                    if ($param->name === substr($word, 1)) {
                        $offset = $i;
                    }
                }

                if ($offset === null) {
                    return '@psalm-database-' . $kind . ' names ' . $word . ', which is not a parameter';
                }

                $params[] = $offset;
            } else {
                foreach (self::RESULTS as $known_result) {
                    if ($word === $known_result) {
                        $result = $known_result;
                    }
                }

                if ($result !== $word) {
                    break;
                }
            }
        }

        $first = $params[0] ?? null;
        $second = $params[1] ?? null;

        $spec = match ($kind) {
            'query' => $first === null
                ? null
                : ($second === null ? ['query', 'sql' => $first] : ['query', 'sql' => $first, 'params' => $second]),
            'prepare' => $first === null ? null : ['prepare', 'sql' => $first],
            'execute' => $first === null ? ['execute'] : ['execute', 'params' => $first],
            'bind' => match (true) {
                $variadic && $first !== null => ['bind', 'values' => $first],
                $first !== null && $second !== null => ['bind', 'key' => $first, 'value' => $second, 'base' => 1],
                default => null,
            },
            'insert' => $first === null || $second === null ? null : ['insert', 'table' => $first, 'data' => $second],
            'update' => $first === null || $second === null ? null : ['update', 'table' => $first, 'data' => $second],
            'select' => $first === null ? null : ['select', 'table' => $first],
            'fetch' => $first === null ? ['fetch'] : ['fetch', 'from' => $first],
            default => null,
        };

        if ($spec === null) {
            return '@psalm-database-' . $kind . ' is missing parameters';
        }

        if ($result !== null) {
            $spec['result'] = $result;
        }

        $storage->database_calls[] = $spec;

        return null;
    }

    /**
     * Connects what the call stores to the database, and returns $return_type holding what it reads from it
     *
     * @param list<Spec> $specs
     * @param array<array-key, Arg> $args
     * @param ?Union $receiver_type the object the method is called on
     */
    public static function taintCall(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $specs,
        ?FunctionLikeStorage $storage,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
        ?Union $receiver_type = null,
    ): Union {
        // the annotations of a function-like say what it stores and reads, wherever its queries come from
        $source = $statements_analyzer->getSource();
        if ($specs === []
            || ($source instanceof FunctionLikeAnalyzer
                && $source->getFunctionLikeStorage($statements_analyzer)->database_calls !== [])
        ) {
            return $return_type;
        }

        $args_by_param = self::getArgsByParam($storage, $args);

        if ($args_by_param === null) {
            return $return_type;
        }

        foreach ($specs as $spec) {
            // pg_query($query) and pg_query($connection, $query)
            if (isset($spec['optional_first']) && count($args) < $spec['optional_first']) {
                $spec = self::shiftOffsets($spec);
            }

            $return_type = match ($spec[0]) {
                'query', 'prepare' => self::taintQuery(
                    $statements_analyzer,
                    $graph,
                    $spec,
                    $args_by_param,
                    $call_location,
                    $return_type,
                ),
                'execute', 'bind' => self::taintStatementValues(
                    $statements_analyzer,
                    $graph,
                    $spec,
                    $args_by_param,
                    $call_location,
                    $return_type,
                    $receiver_type,
                ),
                'insert', 'update' => self::taintTableWrite(
                    $statements_analyzer,
                    $graph,
                    $spec,
                    $args_by_param,
                    $call_location,
                    $return_type,
                ),
                'select' => self::taintTableRead(
                    $statements_analyzer,
                    $graph,
                    $spec,
                    $args_by_param,
                    $call_location,
                    $return_type,
                ),
                'fetch' => self::taintFetch(
                    $statements_analyzer,
                    $graph,
                    $spec,
                    $args_by_param,
                    $call_location,
                    $return_type,
                    $receiver_type,
                ),
            };
        }

        return $return_type;
    }

    /**
     * Records the prepared statements the value assigned holds for the node of the variable it is assigned to
     *
     * @psalm-capabilities read-props|write-props
     */
    public static function recordAssignment(TaintFlowGraph $graph, Union $assigned_type, DataFlowNode $var_node): void
    {
        $statements = $graph->getDatabaseStatements($assigned_type->parent_nodes);

        if ($statements !== []) {
            $graph->addDatabaseStatements($var_node, $statements);
        }
    }

    /**
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function taintQuery(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
    ): Union {
        $sql_arg = isset($spec['sql']) ? ($args[$spec['sql']][0] ?? null) : null;

        if ($sql_arg === null) {
            return $return_type;
        }

        $holes = [];
        $sql = self::getText($statements_analyzer, $sql_arg->value, $holes);
        $parsed = SqlStatement::parse($sql);

        $statement = self::getStatementNode($statements_analyzer, $graph, $spec, $args, $call_location);

        // a query that can't be parsed can store and read anything

        if (isset($spec['params'])) {
            foreach ($args[$spec['params']] ?? [] as $params_arg) {
                self::addPathsFromArg($statements_analyzer, $graph, $params_arg, $statement, '=');
            }
        }

        if ($parsed === null) {
            $parsed = [
                'writes' => [[
                    'table' => null,
                    'column' => null,
                    'value' => [
                        'holes' => self::getAllHoles($holes),
                        'placeholders' => [null],
                        'columns' => [[null, null]],
                    ],
                ]],
                'reads' => [[
                    'keys' => [],
                    'any_key' => true,
                    'all_columns' => false,
                    'value' => ['holes' => [], 'placeholders' => [], 'columns' => [[null, null]]],
                ]],
            ];
        }

        foreach ($parsed['writes'] as $offset => $write) {
            $write_node = DataFlowNode::getForAssignment('database write #' . $offset, $call_location);

            if (!self::addPathsFromValue(
                $statements_analyzer,
                $graph,
                $write['value'],
                $holes,
                $statement,
                $write_node,
                $call_location,
            )) {
                continue;
            }

            self::addWrite($graph, $write['table'], $write['column'], $write_node);
        }

        $result = $spec['result'] ?? 'result';
        // a prepared statement is given values later, even if it selects nothing
        if ($result === 'none' || ($parsed['reads'] === [] && ($spec[0] !== 'prepare' || isset($spec['name'])))) {
            return $return_type;
        }

        // the rows of a statement named by a literal are given by the calls executing it
        $result_node = isset($spec['name'])
            ? self::getNamedStatementRows($statements_analyzer, $graph, $spec, $args)
            : DataFlowNode::getForAssignment('database result', $call_location);
        $graph->addNode($result_node);
        $row_node = $result_node;

        if ($result === 'rows') {
            $row_node = DataFlowNode::getForAssignment('database row', $call_location);
            $graph->addNode($row_node);
            $graph->addPath($row_node, $result_node, 'arrayvalue-assignment');
        }

        foreach ($parsed['reads'] as $offset => $read) {
            $read_node = DataFlowNode::getForAssignment('database column #' . $offset, $call_location);
            self::addPathsFromValue(
                $statements_analyzer,
                $graph,
                $read['value'],
                $holes,
                $statement,
                $read_node,
                $call_location,
            );

            if ($result === 'value' || $result === 'column' || $result === 'value_or_row') {
                if ($offset === 0) {
                    $graph->addPath($read_node, $result_node, $result === 'column' ? 'arrayvalue-assignment' : '=');
                }

                if ($result !== 'value_or_row') {
                    continue;
                }
            }

            if ($read['any_key']) {
                $graph->addPath($read_node, $row_node, 'arrayvalue-assignment');
            }

            foreach ($read['keys'] as $key) {
                // a node of its own for each key: there is one edge between two nodes
                $key_node = DataFlowNode::getForAssignment('database column #' . $offset . ' ' . $key, $call_location);
                $graph->addNode($key_node);
                $graph->addPath($read_node, $key_node, '=');
                $graph->addPath($key_node, $row_node, 'arrayvalue-assignment-' . self::getKey($key));
            }

            if ($read['all_columns']) {
                $tables = [];
                foreach ($read['value']['columns'] as [$table]) {
                    $tables[] = $table;
                }

                self::addAllColumns(
                    $graph,
                    $tables,
                    $read_node,
                    $row_node,
                    'database column #' . $offset,
                    $call_location,
                );
            }
        }

        if (isset($spec['name'])) {
            return $return_type;
        }

        if ($spec[0] === 'prepare') {
            $graph->addDatabaseStatements($result_node, [$statement->id => $statement]);
        }

        return $return_type->addParentNodes([$result_node->id => $result_node]);
    }

    /**
     * What `execute` and `bind` give to the placeholders of a prepared statement
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function taintStatementValues(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
        ?Union $receiver_type,
    ): Union {
        $statements = self::getStatements($statements_analyzer, $graph, $spec, $args, $receiver_type);
        $values_node = DataFlowNode::getForAssignment('database parameters', $call_location);

        if ($spec[0] === 'execute' && isset($spec['name']) && ($spec['result'] ?? 'result') !== 'none') {
            $rows_node = self::getNamedStatementRows($statements_analyzer, $graph, $spec, $args);
            $return_type = $return_type->addParentNodes([$rows_node->id => $rows_node]);
        } elseif ($spec[0] === 'execute' && ($spec['result'] ?? 'none') !== 'none') {
            // a result apart from the statement, which holds the rows (`@psalm-database-execute $params result`)
            $fetch_spec = ['fetch', 'result' => $spec['result'] ?? 'result'];
            if (isset($spec['statement'])) {
                $fetch_spec['from'] = $spec['statement'];
            }

            $return_type = self::taintFetch(
                $statements_analyzer,
                $graph,
                $fetch_spec,
                $args,
                $call_location,
                $return_type,
                $receiver_type,
            );
        }
        $has_values = false;

        if ($spec[0] === 'execute') {
            foreach ($args[$spec['params'] ?? -1] ?? [] as $params_arg) {
                $has_values = self::addPathsFromArg($statements_analyzer, $graph, $params_arg, $values_node, '=')
                    || $has_values;
            }
        } elseif (isset($spec['values'])) {
            // the values of the placeholders in their order (mysqli_stmt::bind_param())
            $position = 0;
            foreach ($args as $offset => $param_args) {
                if ($offset < $spec['values']) {
                    continue;
                }

                foreach ($param_args as $arg) {
                    $value_node = DataFlowNode::getForAssignment('database parameter ' . $position, $call_location);
                    if (self::addPathsFromArg($statements_analyzer, $graph, $arg, $value_node, '=')) {
                        $graph->addNode($value_node);
                        $graph->addPath($value_node, $values_node, 'arrayvalue-assignment-\'' . $position . '\'');
                        $has_values = true;
                    }

                    $position++;
                }
            }
        } else {
            $key_arg = $args[$spec['key'] ?? -1][0] ?? null;
            $key_type = $key_arg === null ? null : $statements_analyzer->node_data->getType($key_arg->value);
            $key = null;

            if ($key_type !== null && $key_type->isSingleIntLiteral()) {
                $key = $key_type->getSingleIntLiteral()->value - ($spec['base'] ?? 0);
            } elseif ($key_type !== null && $key_type->isSingleStringLiteral()) {
                $key = ltrim($key_type->getSingleStringLiteral()->value, ':');
            }

            foreach ($args[$spec['value'] ?? -1] ?? [] as $value_arg) {
                $has_values = self::addPathsFromArg(
                    $statements_analyzer,
                    $graph,
                    $value_arg,
                    $values_node,
                    $key === null ? 'arrayvalue-assignment' : 'arrayvalue-assignment-\'' . $key . '\'',
                ) || $has_values;
            }
        }

        if (!$has_values) {
            return $return_type;
        }

        $graph->addNode($values_node);

        if ($statements === []) {
            // a statement the analysis doesn't know: its values can be stored anywhere
            $write_node = DataFlowNode::getForAssignment('database write', $call_location);
            $graph->addNode($write_node);
            $graph->addPath($values_node, $write_node, 'arrayvalue-fetch');
            self::addWrite($graph, null, null, $write_node);

            return $return_type;
        }

        foreach ($statements as $statement) {
            $graph->addPath($values_node, $statement, '=');
        }

        return $return_type;
    }

    /**
     * What `insert` and `update` store in the columns of a table
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function taintTableWrite(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
    ): Union {
        $table = self::getTableName($statements_analyzer, $args[$spec['table'] ?? -1][0] ?? null);

        foreach ($args[$spec['data'] ?? -1] ?? [] as $data_arg) {
            $data_type = $statements_analyzer->node_data->getType($data_arg->value);
            $keyed = $data_type !== null && $data_type->isSingle() ? $data_type->getSingleAtomic() : null;
            $data_node = DataFlowNode::getForAssignment('database data', $call_location);

            if (!self::addPathsFromArg($statements_analyzer, $graph, $data_arg, $data_node, '=')) {
                continue;
            }

            $graph->addNode($data_node);

            $columns = [];
            if ($keyed instanceof TKeyedArray && $keyed->fallback_params === null) {
                foreach ($keyed->properties as $key => $_) {
                    $columns[] = $data_arg->unpack ? null : (string) $key;
                }
            } else {
                $columns[] = null;
            }

            foreach ($columns as $column) {
                $write_node = DataFlowNode::getForAssignment('database write ' . ($column ?? '*'), $call_location);
                $graph->addNode($write_node);
                $graph->addPath(
                    $data_node,
                    $write_node,
                    $column === null ? 'arrayvalue-fetch' : 'arrayvalue-fetch-\'' . $column . '\'',
                );
                self::addWrite($graph, $table, $column, $write_node);
            }
        }

        return $return_type;
    }

    /**
     * The rows of a table `select` gives (pg_select())
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function taintTableRead(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
    ): Union {
        $table = self::getTableName($statements_analyzer, $args[$spec['table'] ?? -1][0] ?? null);

        $columns_node = DataFlowNode::getForAssignment('database columns', $call_location);
        $graph->addNode($columns_node);
        TaintStore::addRead($graph, self::STORE, [self::getName($table, null)], $columns_node, '=');

        $row_node = DataFlowNode::getForAssignment('database row', $call_location);
        $graph->addNode($row_node);
        self::addAllColumns($graph, [$table], $columns_node, $row_node, 'database columns', $call_location);

        $result_node = DataFlowNode::getForAssignment('database result', $call_location);
        $graph->addNode($result_node);
        // each row holds the columns under their names
        $graph->addPath($row_node, $result_node, 'arrayvalue-assignment');

        return $return_type->addParentNodes([$result_node->id => $result_node]);
    }

    /**
     * What a call returns of the rows the result it is given holds
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function taintFetch(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        CodeLocation $call_location,
        Union $return_type,
        ?Union $receiver_type,
    ): Union {
        $from_type = isset($spec['from'])
            ? self::getArgType($statements_analyzer, $args[$spec['from']][0] ?? null)
            : $receiver_type;

        if ($from_type === null || $from_type->parent_nodes === []) {
            return $return_type;
        }

        $result = $spec['result'] ?? 'result';
        $fetch_node = DataFlowNode::getForAssignment('database fetch', $call_location);
        $graph->addNode($fetch_node);

        [$path, $wrap] = match ($result) {
            // the first column of the next row: what its position names
            'value' => ['arrayvalue-fetch-\'0\'', null],
            'column' => ['arrayvalue-fetch-\'0\'', 'arrayvalue-assignment'],
            'any_value' => ['arrayvalue-fetch', null],
            'any_column' => ['arrayvalue-fetch', 'arrayvalue-assignment'],
            'rows' => ['=', 'arrayvalue-assignment'],
            default => ['=', null],
        };

        $return_node = $fetch_node;
        if ($wrap !== null) {
            $return_node = DataFlowNode::getForAssignment('database fetched', $call_location);
            $graph->addNode($return_node);
            $graph->addPath($fetch_node, $return_node, $wrap);
        }

        foreach ($from_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $fetch_node, $path);
        }

        $statements = $graph->getDatabaseStatements($from_type->parent_nodes);
        if ($statements !== []) {
            $graph->addDatabaseStatements($return_node, $statements);
        }

        return $return_type->addParentNodes([$return_node->id => $return_node]);
    }

    /**
     * Stores what flows into $write in the column of the table, and in the rows of the table under the column's name
     */
    private static function addWrite(TaintFlowGraph $graph, ?string $table, ?string $column, DataFlowNode $write): void
    {
        TaintStore::addWrite($graph, self::STORE, [self::getName($table, $column)], $write, '=');

        $rows = [$table === null ? [false, ''] : [true, $table]];

        if ($column === null) {
            TaintStore::addWrite($graph, self::ROWS_STORE, $rows, $write, 'arrayvalue-assignment');

            return;
        }

        TaintStore::addWrite($graph, self::ROWS_STORE, $rows, $write, 'arrayvalue-assignment-' . self::getKey($column));
    }

    /**
     * The key of a column in the rows, known but for its case: the rows name it as the table does, or as the query
     * writes it (MySQL), lowercase if it isn't quoted (PostgreSQL), ...
     *
     * @psalm-pure
     */
    private static function getKey(int|string $column): string
    {
        return is_int($column) ? '\'' . $column . '\'' : '\'' . strtolower($column) . '\'~';
    }

    /**
     * Adds the columns of the tables to $row, as `*` gives them: under their names, and at positions not known (any
     * integer key) what $columns holds, any column of the tables
     *
     * @param list<?string> $tables
     */
    private static function addAllColumns(
        TaintFlowGraph $graph,
        array $tables,
        DataFlowNode $columns,
        DataFlowNode $row,
        string $label,
        CodeLocation $call_location,
    ): void {
        $names = [];
        foreach ($tables as $table) {
            $names[] = $table === null ? [false, ''] : [true, $table];
        }

        $named_node = DataFlowNode::getForAssignment($label . ' by name', $call_location);
        $graph->addNode($named_node);
        TaintStore::addRead($graph, self::ROWS_STORE, $names, $named_node, '=');
        $graph->addPath($named_node, $row, '=');

        $graph->addPath($columns, $row, 'arrayvalue-assignment-' . DataFlowGraph::INTEGER_KEY);
    }

    /**
     * Connects the parts of the query and the placeholders a value is made of, and what it copies, to $node
     *
     * @param Value $value
     * @param list<Expr> $holes
     * @return bool whether anything flows into $node
     */
    private static function addPathsFromValue(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $value,
        array $holes,
        ?DataFlowNode $statement,
        DataFlowNode $node,
        CodeLocation $call_location,
    ): bool {
        $has_parents = false;

        foreach ($value['holes'] as $hole) {
            $hole_type = isset($holes[$hole]) ? $statements_analyzer->node_data->getType($holes[$hole]) : null;
            foreach ($hole_type?->parent_nodes ?? [] as $parent) {
                $graph->addPath($parent, $node, '=');
                $has_parents = true;
            }
        }

        if ($statement !== null) {
            foreach ($value['placeholders'] as $offset => $placeholder) {
                $placeholder_node = DataFlowNode::getForAssignment(
                    $node->label . ' placeholder #' . $offset,
                    $call_location,
                );
                $graph->addNode($placeholder_node);
                $graph->addPath(
                    $statement,
                    $placeholder_node,
                    $placeholder === null ? 'arrayvalue-fetch' : 'arrayvalue-fetch-\'' . $placeholder . '\'',
                );
                $graph->addPath($placeholder_node, $node, '=');
                $has_parents = true;
            }
        }

        $names = [];
        foreach ($value['columns'] as [$table, $column]) {
            $names[] = self::getName($table, $column);
        }

        if ($names !== []) {
            TaintStore::addRead($graph, self::STORE, $names, $node, '=');
            $has_parents = true;
        }

        if ($has_parents) {
            $graph->addNode($node);
        }

        return $has_parents;
    }

    /**
     * The node the values of the placeholders of the query go through: of the statement named by `name`, else of the
     * call
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function getStatementNode(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        CodeLocation $call_location,
    ): DataFlowNode {
        if (isset($spec['name'])) {
            $name_type = self::getArgType($statements_analyzer, $args[$spec['name']][0] ?? null);
            if ($name_type !== null && $name_type->isSingleStringLiteral()) {
                $node = DataFlowNode::getForPropertyFetch(
                    self::STORE . ' statement ' . $name_type->getSingleStringLiteral()->value,
                );
                $graph->addNode($node);
                $graph->addSharedState($node);

                return $node;
            }
        }

        $node = DataFlowNode::getForAssignment('database statement', $call_location);
        $graph->addNode($node);

        return $node;
    }

    /**
     * The rows the statement named by a literal gives, or of all of them
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     */
    private static function getNamedStatementRows(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
    ): DataFlowNode {
        $name_type = self::getArgType($statements_analyzer, $args[$spec['name'] ?? -1][0] ?? null);
        $name = $name_type !== null && $name_type->isSingleStringLiteral()
            ? $name_type->getSingleStringLiteral()->value
            : '*';

        $node = DataFlowNode::getForPropertyFetch(self::STORE . ' rows of statement ' . $name);
        $graph->addNode($node);
        $graph->addSharedState($node);

        if ($name !== '*') {
            // a statement executed by a name the analysis doesn't know can be this one
            $all_node = DataFlowNode::getForPropertyFetch(self::STORE . ' rows of statement *');
            $graph->addNode($all_node);
            $graph->addSharedState($all_node);
            $graph->addPath($node, $all_node, '=');
        }

        return $node;
    }

    /**
     * The nodes of the prepared statements `execute` and `bind` give values to
     *
     * @param Spec $spec
     * @param array<int, list<Arg>> $args
     * @return array<string, DataFlowNode>
     */
    private static function getStatements(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $spec,
        array $args,
        ?Union $receiver_type,
    ): array {
        if (isset($spec['name'])) {
            $name_type = self::getArgType($statements_analyzer, $args[$spec['name']][0] ?? null);
            if ($name_type === null || !$name_type->isSingleStringLiteral()) {
                return [];
            }

            $node = DataFlowNode::getForPropertyFetch(
                self::STORE . ' statement ' . $name_type->getSingleStringLiteral()->value,
            );
            $graph->addNode($node);
            $graph->addSharedState($node);

            return [$node->id => $node];
        }

        $statement_type = isset($spec['statement'])
            ? self::getArgType($statements_analyzer, $args[$spec['statement']][0] ?? null)
            : $receiver_type;

        return $statement_type === null ? [] : $graph->getDatabaseStatements($statement_type->parent_nodes);
    }

    /**
     * @return bool whether anything flows from the argument
     */
    private static function addPathsFromArg(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        Arg $arg,
        DataFlowNode $node,
        string $path_type,
    ): bool {
        $parents = $statements_analyzer->node_data->getType($arg->value)?->parent_nodes ?? [];

        foreach ($parents as $parent) {
            $graph->addPath($parent, $node, $path_type);
        }

        if ($parents !== []) {
            $graph->addNode($node);
        }

        return $parents !== [];
    }

    /**
     * The text of the SQL, where each part whose text the analysis doesn't know is a hole (see SqlStatement)
     *
     * @param list<Expr> $holes the parts that are holes, in their order
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function getText(StatementsAnalyzer $statements_analyzer, Expr $expr, array &$holes): string
    {
        $type = $statements_analyzer->node_data->getType($expr);

        if ($type !== null && $type->isSingleStringLiteral()) {
            return $type->getSingleStringLiteral()->value;
        }

        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof Concat) {
            return self::getText($statements_analyzer, $expr->left, $holes)
                . self::getText($statements_analyzer, $expr->right, $holes);
        }

        if ($expr instanceof InterpolatedString) {
            $text = '';
            foreach ($expr->parts as $part) {
                $text .= $part instanceof InterpolatedStringPart
                    ? $part->value
                    : self::getText($statements_analyzer, $part, $holes);
            }

            return $text;
        }

        $holes[] = $expr;

        return SqlStatement::HOLE_MARKER . (count($holes) - 1) . SqlStatement::HOLE_MARKER;
    }

    /**
     * @param list<Expr> $holes
     * @return list<int>
     * @psalm-pure
     */
    private static function getAllHoles(array $holes): array
    {
        $numbers = [];
        foreach ($holes as $number => $_) {
            $numbers[] = $number;
        }

        return $numbers;
    }

    /**
     * The name of the column in the store: exact, or the start of the names of the columns of the table, or of all
     *
     * @return array{bool, string}
     * @psalm-pure
     */
    private static function getName(?string $table, ?string $column): array
    {
        if ($table === null) {
            return [false, ''];
        }

        if ($column === null) {
            return [false, $table . '.'];
        }

        return [true, $table . '.' . strtolower($column)];
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getTableName(StatementsAnalyzer $statements_analyzer, ?Arg $arg): ?string
    {
        $type = self::getArgType($statements_analyzer, $arg);

        if ($type === null || !$type->isSingleStringLiteral()) {
            return null;
        }

        $parsed = SqlStatement::parse('SELECT x FROM ' . $type->getSingleStringLiteral()->value);
        $column = $parsed['reads'][0]['value']['columns'][0] ?? null;

        return $column === null ? null : $column[0];
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function getArgType(StatementsAnalyzer $statements_analyzer, ?Arg $arg): ?Union
    {
        return $arg === null ? null : $statements_analyzer->node_data->getType($arg->value);
    }

    /**
     * The arguments given to each parameter, by offset: positional only when the parameters aren't known
     *
     * @param array<array-key, Arg> $args
     * @return array<int, list<Arg>>|null
     * @psalm-capabilities read-props
     */
    private static function getArgsByParam(?FunctionLikeStorage $storage, array $args): ?array
    {
        if ($storage !== null) {
            return TaintStore::getArgsByParam($storage, $args);
        }

        $by_param = [];
        foreach ($args as $offset => $arg) {
            if ($arg->name !== null || $arg->unpack || !is_int($offset)) {
                return null;
            }

            $by_param[$offset] = [$arg];
        }

        return $by_param;
    }

    /**
     * The spec of a call without its optional first argument
     *
     * @param Spec $spec
     * @return Spec
     * @psalm-pure
     */
    private static function shiftOffsets(array $spec): array
    {
        foreach (['sql', 'params', 'statement', 'name', 'key', 'value', 'values', 'table', 'data', 'from'] as $key) {
            $offset = $spec[$key] ?? null;
            if (is_int($offset)) {
                $spec[$key] = $offset - 1;
            }
        }

        return $spec;
    }
}
