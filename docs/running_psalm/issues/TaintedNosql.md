# TaintedNosql

Emitted when user-controlled input can be passed into a NoSQL query (e.g. a MongoDB filter or command).

```php
<?php

function getUser() : MongoDB\Driver\Query {
    $filter = ['username' => $_GET['username']];
    return new MongoDB\Driver\Query($filter);
}
```

Passing user input directly into a MongoDB filter allows an attacker to inject query
operators (such as `$ne`, `$gt` or `$where`), bypassing the intended query logic.
