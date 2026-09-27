# UnusedProperty

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
private property.

Properties used in constructor only are considered unused. Use normal variables instead.

If this property is genuinely part of your public API — used from outside the analysed codebase (for example a library entry point, or a property read only by a framework) — annotate the containing class with `@api` (or `@psalm-api`) — mark the whole class rather than the individual property, since a public-API property almost always belongs to a public-API class. Otherwise the property is dead code and should be removed: a property reported as unused in a class that is **not** already marked `@api` is almost always genuinely unused, so annotating the individual property with `@api` to silence the report is very rarely the right fix.

See [`@api`](../../annotating_code/supported_annotations.md#api-psalm-api) for the full guidance; note that `@psalm-suppress` is not a substitute for `@api`, as it silences the report without marking the property as used.

```php
<?php

class A {
    /** @var string|null */
    private $foo;

    /** @var int|null */
    private $bar;

    public function getFoo(): ?string {
        return $this->foo;
    }
}

$a = new A();
echo $a->getFoo();
```
