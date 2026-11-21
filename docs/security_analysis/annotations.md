# Security analysis annotations

## `@psalm-taint-source <taint-type> [return['key']]`

See [Custom taint sources](custom_taint_sources.md#taint-source-annotation).

## `@psalm-taint-sink <taint-type #conditional> <param-name>`

See [Custom taint sinks](custom_taint_sinks.md) and [Conditional sinks](custom_taint_sinks.md#conditional-sinks).

## `@psalm-taint-escape <taint-type #conditional>`

See [Your own escaping functions](index.md#your-own-escaping-functions).

## `@psalm-taint-unescape <taint-type>`

See [What Psalm can't see](index.md#what-psalm-cant-see).

## Taint type complements

Wherever these annotations take a `<taint-type>`, `~(<taint-type>|<taint-type>)` (or `~<taint-type>` for one) stands
for every taint but the ones listed: the built-in ones (`user_secret` and `system_secret` too), the custom ones,
including those other annotations only register later. The ones listed may be aliases, like `input`. Write it without
spaces.

```php
<?php // --taint-analysis

/**
 * Taken from the request line, which can't hold a line break: any input but a header injection
 *
 * @psalm-taint-source ~(header|user_secret|system_secret)
 */
function getRequestPath(): string {}
```

The taints the native types of a value can't hold are still removed from it: a `string` returned by
`getRequestPath()` above can't be a NoSQL query.

## `@psalm-cli-only`

See [Command-line code](index.md#command-line-code).

## `@psalm-cli-only-instances`

On a final class: its instances only exist in command-line processes, so the code that requires one only runs from the
command line. See [Classes only instantiated from the command line](index.md#classes-only-instantiated-from-the-command-line).

## `@psalm-taint-property-bag $<property>`

On a class: the array property `$<property>` holds the properties of its objects under their names.
See [Properties held in an array property](taint_flow.md#properties-held-in-an-array-property)

## `@psalm-taint-specialize`

See [Specializing functions](index.md#specializing-functions) and [Specializing classes](index.md#specializing-classes).

## `@psalm-flow [proxy <function-like>] ( <arg>, [ <arg>, ] ) [ -> return ]`

See [Taint Flow](taint_flow.md#optimized-taint-flow).

Each `<arg>` is a parameter, like `$value`. With `-> return`, an `<arg>` can also be:

- `$this`: the object the method is called on
- a parameter followed by `()`, like `$compute()`: what the callables passed to that parameter return

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $<callable>()`

The callables given to the parameter `$<callable>` are called with what is given to the `<arg>`s. See
[Arguments to callables](taint_flow.md#arguments-to-callables).

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> <Class>::$<store>[<key>]` and `@psalm-flow <Class>::$<store>[<key>] -> return`

See [Keyed stores](taint_flow.md#keyed-stores)

## Database annotations

For security analysis: these annotations say which queries the methods of a database layer run (your own wrapper,
a library's connection class), so that what one query stores in a column taints what the queries reading that column
return. Psalm reads the SQL at each call and ignores the queries the annotated method's body runs. See
[Databases](taint_flow.md#databases).

### `@psalm-database-query $sql [$params] [result]`

The method runs the SQL given to `$sql`, with the values of its placeholders given to `$params`: a list for `?`, an
array keyed by name for `:name`.

```php
<?php // --taint-analysis
final class Db
{
    /**
     * @psalm-database-query $sql $params none
     */
    public function execute(string $sql, array $params = []): void {}

    /**
     * @psalm-database-query $sql $params rows
     */
    public function all(string $sql, array $params = []): array {}

    /**
     * @psalm-database-query $sql $params value
     */
    public function value(string $sql, array $params = []): mixed {}
}

function run(Db $db): void {
    $db->execute('INSERT INTO users (name, bio) VALUES (?, ?)', ['admin', $_GET['bio'] ?? '']);

    foreach ($db->all('SELECT name, bio FROM users') as $user) {
        echo (string) $user['bio'];  // TaintedHtml
        echo (string) $user['name']; // no issue
    }

    echo (string) $db->value('SELECT bio FROM users WHERE id = ?', [1]);  // TaintedHtml
    echo (string) $db->value('SELECT name FROM users WHERE id = ?', [1]); // no issue
}
```

The last word says what the method returns of the rows the query selects:

| `result`           | The method returns                                                  | Like                          |
|--------------------|---------------------------------------------------------------------|-------------------------------|
| `result` (default) | a result to fetch the rows from with `@psalm-database-fetch`, or a row | `PDO::query()`             |
| `rows`             | a list of rows                                                      | `PDOStatement::fetchAll()`    |
| `value`            | the first column of the first row                                   | `PDOStatement::fetchColumn()` |
| `column`           | a list of the first column of each row                              | `fetchAll(PDO::FETCH_COLUMN)` |
| `none`             | nothing read from the database                                      | `PDO::exec()`                 |

### `@psalm-database-prepare $sql`, `@psalm-database-execute [$params] [result]` and `@psalm-database-bind $key $value`

`prepare` returns a statement for the SQL given to `$sql`. The methods of the statement give the values of its
placeholders:

- `@psalm-database-execute $params`: the values given to `$params`, as for `@psalm-database-query`. Without `result`,
  the statement then holds the rows, to fetch with `@psalm-database-fetch`; with a `result`, the method returns them as
  that `result` says.
- `@psalm-database-bind $key $value`: the value given to `$value`, for the placeholder given to `$key` (`1` for the
  first `?`, `'bio'` or `':bio'` for `:bio`).
- `@psalm-database-bind ...$values`: the values given to `$values` and the parameters after it, one per placeholder
  in their order, as for `mysqli_stmt::bind_param()`.

```php
<?php // --taint-analysis
final class Connection
{
    /**
     * @psalm-database-prepare $sql
     */
    public function prepare(string $sql): Statement {}
}

final class Statement
{
    /**
     * @psalm-database-bind $param $value
     */
    public function bindValue(int|string $param, mixed $value): void {}

    /**
     * @psalm-database-execute $params
     */
    public function execute(array $params = []): void {}

    /**
     * @psalm-database-execute $params result
     */
    public function executeQuery(array $params = []): Result {}

    /**
     * @return array<string, mixed>|false
     * @psalm-database-fetch
     */
    public function fetch(): array|false {}
}

final class Result
{
    /**
     * @return list<array<string, mixed>>
     * @psalm-database-fetch rows
     */
    public function fetchAll(): array {}
}

function run(Connection $db): void {
    $insert = $db->prepare('INSERT INTO users (name, bio) VALUES (?, :bio)');
    $insert->bindValue(1, 'admin');
    $insert->bindValue('bio', $_GET['bio'] ?? '');
    $insert->execute();

    $select = $db->prepare('SELECT name, bio FROM users WHERE id = ?');
    $select->execute([1]);
    $user = $select->fetch();
    if ($user !== false) {
        echo (string) $user['bio'];  // TaintedHtml
        echo (string) $user['name']; // no issue
    }

    foreach ($db->prepare('SELECT bio FROM users')->executeQuery()->fetchAll() as $user) {
        echo (string) $user['bio']; // TaintedHtml
    }
}
```

### `@psalm-database-fetch [$result] [result]`

The method returns the rows held by the result given to `$result`, or, without `$result`, by the object it is called
on (a statement, a result). The last word says how, as in the table of `@psalm-database-query`; with the default
`result`, the method returns one row. See the example above.

### `@psalm-database-insert $table $data`, `@psalm-database-update $table $data` and `@psalm-database-select $table`

`insert` and `update` store each value of the array given to `$data` in the column its key names, in the table given
to `$table`. `select` returns the rows of that table, as `SELECT *` gives them.

```php
<?php // --taint-analysis
final class Table
{
    /**
     * @psalm-database-insert $table $data
     */
    public function insert(string $table, array $data): void {}

    /**
     * @psalm-database-update $table $data
     */
    public function update(string $table, array $data, array $where): void {}

    /**
     * @return list<array<string, mixed>>
     * @psalm-database-select $table
     */
    public function select(string $table): array {}
}

function run(Table $db): void {
    $db->insert('users', ['name' => 'admin', 'bio' => '']);
    $db->update('users', ['bio' => $_GET['bio'] ?? ''], ['id' => 1]);

    foreach ($db->select('users') as $user) {
        echo (string) $user['bio'];  // TaintedHtml
        echo (string) $user['name']; // no issue
    }

    foreach ($db->select('posts') as $post) {
        echo (string) $post['title']; // no issue
    }
}
```

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $this-><name>` and `@psalm-flow $this-><name> -> return`

`<name>` is a parameter giving the name of the property, like `$name`, or `*` for any property.
See [Properties set and got by name](taint_flow.md#properties-set-and-got-by-name)

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $this->{key}` and `@psalm-flow $this->{key} -> return[key]`

Each property is set to, or returned under, its name as a key of the array.
See [Properties set and got by name](taint_flow.md#properties-set-and-got-by-name)

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $this-><property>[<key>]` and `@psalm-flow $this-><property>[<key>] -> return`

`<property>` is the name of the property, like `vars`. The optional `[<key>]` is the element of that array property
under the key given to a parameter, like `[$name]`, or `[*]` for any element.
See [A property and its elements](taint_flow.md#a-property-and-its-elements)
