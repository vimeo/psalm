# TaintedNosql

Emitted when user-controlled input can be passed into a NoSQL query (e.g. a MongoDB filter or command).

Unlike SQL injection, NoSQL injection does not depend on string concatenation. It happens
when an attacker controls the *structure* of a query document — typically by supplying an
array/object instead of a scalar. For example, a request like `?username[$ne]=` causes
`$_GET['username']` to be the array `['$ne' => '']`, which turns an equality match into a
"not equal" match and can bypass authentication.

Because of this, only values that can hold an array (or object) can carry the `nosql` taint —
a plain `string` can never be a NoSQL query, so casting user input to `string` removes the taint.

```php
<?php

function getUser(MongoDB\Driver\Manager $manager): array {
    // $_GET["username"] may be an array such as ["$ne" => null]
    $filter = ["username" => $_GET["username"]];
    $query = new MongoDB\Driver\Query($filter);

    return $manager->executeQuery("db.users", $query)->toArray();
}
```

## Safe alternatives

Cast user input to a scalar so it can only ever be a literal value, never a query operator:

```php
<?php

function getUser(): MongoDB\Driver\Query {
    // (string) forces a literal match; the nosql taint is removed
    return new MongoDB\Driver\Query(["username" => (string) $_GET["username"]]);
}
```

Or route the filter through a sanitizer annotated with `@psalm-taint-escape nosql`:

```php
<?php

/**
 * @param array<string, string> $filter
 * @return array<string, string>
 * @psalm-taint-escape nosql
 */
function sanitize_mongo_filter(array $filter): array {
    // e.g. reject any value that is not a scalar
    return $filter;
}

function getUser(): MongoDB\Driver\Query {
    $filter = sanitize_mongo_filter(["username" => (string) $_GET["username"]]);
    return new MongoDB\Driver\Query($filter);
}
```
