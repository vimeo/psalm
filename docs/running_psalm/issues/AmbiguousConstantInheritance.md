# AmbiguousConstantInheritance

Emitted when a constant is inherited from multiple sources.

```php
<?php

interface Foo
{
    /** @var non-empty-string */
    public const CONSTANT='foo';
}

interface Bar
{
    /**
     * @var non-empty-string
     */
    public const CONSTANT='bar';
}

interface Baz extends Foo, Bar {}
```

```php
<?php

interface Foo
{
    /** @var non-empty-string */
    public const CONSTANT='foo';
}

class Bar
{
    /** @var non-empty-string */
    public const CONSTANT='bar';
}

class Baz extends Bar implements Foo {}
```
