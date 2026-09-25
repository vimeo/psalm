# MissingAbstractPureAnnotation

Emitted when an abstract method does not have a `@psalm-pure`, `@psalm-impure` or [`@psalm-capabilities`](../../annotating_code/supported_annotations.md#psalm-capabilities)
annotation, which says what every implementation may do, for example:

* `@psalm-pure` - no side effects and no property accesses
* `@psalm-capabilities read-props` - may read properties, but not write them
* `@psalm-capabilities write-this-props` - may also write the properties of `$this`
* `@psalm-impure` - may do anything (not recommended)

This issue is emitted to aid [security analysis](https://psalm.dev/docs/security_analysis/), which works best when all explicitly pure functions and methods are marked as pure.  

```php
<?php

interface a {
    public function someMethod(): void;
}
```
