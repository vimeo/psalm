# MissingOverrideAttribute

Emitted when the config flag `ensureOverrideAttribute` is set to `true` and a method on a child class or interface overrides a method on a parent, but no `Override` attribute is present. From PHP 8.5 the same holds for a property that overrides a property of a parent class or implements one of an interface (a private parent property and a property coming from a trait don't count). From PHP 8.6 it holds for a class constant or an enum case that overrides a public or protected constant of a parent class or interface too.

```php
<?php // ensureOverrideAttribute="true"

class A {
    function receive(): void
    {
    }
}

class B extends A {
    function receive(): void
    {
    }
}
```

```php
<?php

class A {
    public string $label = 'a';
}

class B extends A {
    public string $label = 'b'; // MissingOverrideAttribute, with phpVersion 8.5
}
```

```php
<?php

class A {
    public const string NAME = 'a';
}

class B extends A {
    public const string NAME = 'a'; // MissingOverrideAttribute, with phpVersion 8.6
}
```

## Why this is bad

Having an `Override` attribute on overridden methods makes intentions clear. Read the [PHP RFC](https://wiki.php.net/rfc/marking_overriden_methods) for more details.

## How to fix

Declare the `#[\Override]` attribute on all indicated methods, or run `vendor/bin/psalter --issues=MissingOverrideAttribute` to let Psalm do it for you.  

Note that the `#[\Override]` attribute is compatible with **all PHP versions**, even PHP 4.  

On PHP 8.0-8.2, require [symfony/polyfill-php83](https://packagist.org/packages/symfony/polyfill-php83) to polyfill the missing Override attribute.  