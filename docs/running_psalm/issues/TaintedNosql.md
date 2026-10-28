# TaintedNosql

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into a NoSQL query (e.g. a MongoDB filter or command).

Unlike SQL injection, NoSQL injection does not depend on string concatenation. It happens
when an attacker controls the *structure* of a query document — typically by supplying an
array/object instead of a scalar. For example, a request like `?username[$ne]=` causes
`$_GET['username']` to be the array `['$ne' => '']`, which turns an equality match into a
"not equal" match and can bypass authentication.

Because of this, only values that can hold an array (or object) can carry the `nosql` taint —
a scalar can never be a NoSQL query, so casting user input to `string`, `int`, `float` or
`bool` removes the taint.

A string decoded back into an array or an object can be a query again, though: what
`json_decode()`, `unserialize()`, `parse_str()`, `parse_ini_string()` and the other decoders
build out of user input carries the `nosql` taint, whatever the type of the string was.

```php
<?php

function getUser(MongoDB\Driver\Manager $manager): array {
    // $_GET["username"] may be an array such as ["$ne" => null]
    $filter = ["username" => $_GET["username"]];
    $query = new MongoDB\Driver\Query($filter);

    return $manager->executeQuery("db.users", $query)->toArray();
}
```

```php
<?php

function findUsers(string $json): MongoDB\Driver\Query {
    // {"username": {"$ne": null}} decodes to a query operator
    return new MongoDB\Driver\Query(json_decode($json, true));
}

findUsers((string) $_POST["filter"]);
```

## Safe alternatives

Cast user input to a scalar so it can only ever be a literal value, never a query operator:

```php
<?php

function getUser(): MongoDB\Driver\Query {
    // (string) forces a literal match; the nosql taint is removed.
    // Casting to (int)/(float)/(bool) works the same way for numeric/boolean fields.
    return new MongoDB\Driver\Query(["username" => (string) $_GET["username"]]);
}
```

Or compare the field with `$eq`, which matches its operand as a value, never as an operator:

```php
<?php

/** @psalm-taint-sink nosql $filter */
function findUsers(array $filter): array {
    return [];
}

function search(string $field): void {
    findUsers(["username" => ['$eq' => $_GET["username"]]]);                // safe

    findUsers(['$expr' => ['$eq' => ['$username', $_GET["username"]]]]);    // TaintedNosql: $expr evaluates operands
    findUsers(["username" => ['$not' => ['$eq' => $_GET["username"]]]]);    // TaintedNosql: not the field's own $eq
    findUsers(["username" => ['$eq' => "admin", '$in' => $_GET["names"]]]); // TaintedNosql: from $in
    findUsers([$field => ['$eq' => $_GET["username"]]]);                    // TaintedNosql: the field isn't a literal
}
```

Or route the filter through a sanitizer annotated with `@psalm-taint-escape nosql`:

```php
<?php

/**
 * Forces every filter value to a scalar so an attacker cannot inject query
 * operators such as ["$ne" => null] through array-valued input.
 *
 * @param array<string, mixed> $filter
 * @return array<string, string>
 * @psalm-taint-escape nosql
 */
function sanitize_mongo_filter(array $filter): array {
    $safe = [];
    foreach ($filter as $field => $value) {
        if (is_array($value)) {
            throw new InvalidArgumentException("Filter values must be scalar");
        }
        $safe[$field] = (string) $value;
    }
    return $safe;
}

function getUser(): MongoDB\Driver\Query {
    $filter = sanitize_mongo_filter(["username" => $_GET["username"]]);
    return new MongoDB\Driver\Query($filter);
}
```
