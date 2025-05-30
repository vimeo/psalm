# HiddenGeneratorReturn

Emitted when trying to return a value from a generator function that does not have a `Generator` return type.

```php
<?php

/**
 * @return iterable<"foo">
 */
function generator(): iterable
{
    yield "foo";

    return 1;
}
```
