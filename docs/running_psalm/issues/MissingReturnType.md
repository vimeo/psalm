# MissingReturnType

Emitted when a function doesn't have a return type defined

```php
<?php

function foo() {
    return "foo";
}
```

Correct with:

```php
<?php

/**
 * @return string 
 */
function foo() {
    return "foo";
}
```
