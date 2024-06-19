# Custom Taint Kinds

[Psalm\Type\TaintKindRegistry](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/TaintKindRegistry.php)
allows plugins to define their own taint kinds and groups. The registry can be accessed via the `Psalm\Config`
singleton.


## Define Kinds

```php
$registry = \Psalm\Config::getInstance()->taint_kind_registry;
$registry->defineKinds([
    'custom-first' => \Example\Package\TaintedCustomA::class,
    'custom-second' => \Example\Package\TaintedCustomB::class,
], 'custom');
```

The example above defines the custom taints `custom-first` and `custom-second`,
maps them to corresponding individual issue classes and organizes them
in a new custom group `custom`.

```php
/**
 * @psalm-taint-source custom-first
 */
function fetchFirst(): string {}
/**
 * Stub for the `custom` group - which includes both defined kinds.
 *
 * @psalm-taint-source custom
 */
function fetchData(): array {}
```

## Define Groups

```php
$registry = \Psalm\Config::getInstance()->taint_kind_registry;
$registry->defineGroup('specific-input', 'html', 'sql');
```

The exmaple above defines a new group `specific-input`, which only holds
a reduced subset (`html` and `sql`) of the default built-in group `input`.

```php
/**
 * @psalm-taint-source specific-input
 */
function fetchData(): array {}
```

## Extend Groups

```php
$registry = \Psalm\Config::getInstance()->taint_kind_registry;
$registry->extendGroup('input', 'custom-first');
```

The example above adds the custom kind `custom-first` to an existing group `input`.

## Define Group Proxies

```php
$registry = \Psalm\Config::getInstance()->taint_kind_registry;
$registry->defineGroupProxy('input-sql', 'input', [
    'sql' => \Example\Package\TaintedSqlSecondOrder::class,
]);
```

The example above is special as it defines `input-sql` to be a proxy for
the existing group `input-sql` and keeps track of that usage. In addition,
the build-in kind `sql` is overloaded with a custom issue class.

This example is known as "Second Order SQL Injection", where data that was
retrieved from a database is reused for new SQL queries. The assumption is,
that the persisted data was not sanitized and might contain user submitted
malicious snippets.

```php
/**
 * @psalm-taint-source input-sql
 */
function fetchAliasFromDatabase(): string {}

/**
 * @psalm-taint-sink sql $query
 * @psalm-taint-specialize
 */
function executeDatabaseQuery($query): array {}

// this would still issue the default `TaintedSql`
$alias = $_GET['alias'];
$query = sprintf('SELECT * FROM comments WHERE alias="%s";', $alias); 
$rows = executeDatabaseQuery($query);

// this would issue the custom `TaintedSqlSecondOrder`
$alias = fetchAliasFromDatabase();
$query = sprintf('SELECT * FROM comments WHERE alias="%s";', $alias); 
$rows = executeDatabaseQuery($query);
```
