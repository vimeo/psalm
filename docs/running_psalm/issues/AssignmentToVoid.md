# AssignmentToVoid

Emitted when assigning from a function that returns `void`:

```php
<?php

function foo() : void {}
$a = foo();
```

## How to fix

You should just be able to remove the assignment:

```php
<?php

function foo() : void {}
foo();
```
