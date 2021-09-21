# InvalidAttribute

Emitted when using an attribute on an element that doesn't match the attribute's target

```php
<?php
namespace Foo;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Table {
    public function __construct(public string $name) {}
}

#[Table("videos")]
function foo() : void {}
```
