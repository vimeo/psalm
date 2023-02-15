# UnusedProperty

Emitted when `--find-dead-code` is turned on and Psalm cannot find any uses of a
private property.

Properties used in constructor only are considered unused. Use normal variables instead.

If this property is used and part of the public API, annotate the containing
class with `@psalm-api`.

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
