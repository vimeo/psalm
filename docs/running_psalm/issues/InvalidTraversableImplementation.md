# InvalidTraversableImplementation

Emitted when class incorrectly implements Traversable. Traversable needs to be
implemented by implementing either `IteratorAggregate` or `Iterator`

```php
<?php

/**
 * @implements Traversable<mixed, mixed>
 */
final class C implements Traversable {} // will cause fatal error
```
