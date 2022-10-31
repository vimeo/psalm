# ReferenceReusedFromConfusingScope

Emitted when a reference assigned in a potentially confusing scope is reused later.
Since PHP doesn't scope loops and ifs the same way most other languages do, a common issue is the reuse of a variable
declared in such a scope. Usually it doesn't matter, because a reassignment to an already-defined variable will just
reassign it, but if the already-defined variable is a reference it will change the value of the referred to variable.

```php
<?php

$arr = [1, 2, 3];
foreach ($arr as &$i) {
    ++$i;
}

// ...more code, after which the usage of $i as a reference has been forgotten by the programmer

for ($i = 0; $i < 10; ++$i) {
    echo $i;
}
// $arr is now [2, 3, 10] instead of the expected [2, 3, 4]
```

To fix the issue, unset the reference after using it in such a scope:

```php
<?php

$arr = [1, 2, 3];
foreach ($arr as &$i) {
    ++$i;
}
unset($i);

for ($i = 0; $i < 10; ++$i) {
    echo $i;
}
// $arr is correctly [2, 3, 4]
```
