# UnusedPsalmSuppress

Emitted when `--find-unused-psalm-suppress` is turned on and Psalm cannot find any uses of a given `@psalm-suppress` annotation

```php
<?php

/** @psalm-suppress InvalidArgument */
echo strlen("hello");
```

This is checked for `@psalm-suppress` annotations in any docblock, including those on classes, interfaces, traits and enums. `@psalm-suppress` of `Tainted*` issues is only checked when running with `--taint-analysis`, since those issues are not computed otherwise.
