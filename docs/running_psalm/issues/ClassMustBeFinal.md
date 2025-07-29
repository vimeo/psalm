# ClassMustBeFinal

Emitted when a non-final, non-abstract class with no child classes is found.  

```php
<?php

class A {}
```

## Why this is bad

Non-final classes are bad for multiple reasons:

- They allow overriding non-final methods and properties, which can lead to unexpected behavior and bugs if implementation details are changed during inheritance of classes not explicitly part of the public API (marked using the `@api` attribute).  
- A corollary of the above is that non-final classes increase the amount of code that must be covered by any backwards-compatibility promise.  
  - In final classes, only public functions/properties/constants must be covered by the backwards compatibility promise.  
  - In non-final classes, all private, protected and public functions/properties/constants must be covered by the backwards compatibility promise (private methods too, because changes to their code may not be compatible with overridden protected/public methods).
- They are not optimized by Opcache and PHP itself, and thus are more expensive to use at runtime.  
- Psalm type inference is more complex and not as exact for non-`final` classes.  

In general, the number of non-final classes in the codebase should be reduced as much as possible, both to speed up code execution and avoid unexpected bugs.  

## How to fix

Recommended, make the class `final`:    

```php
<?php

final class A {}
```

The above can also be automated using `vendor/bin/psalm --alter --issues=ClassMustBeFinal`.  

If inheritance should still be allowed, reduce the surface covered by the backwards compatibility promise by making the class abstract (containing **only** the logic that should be overridable), and move any non-overridable logic to a new `A` class:

```php
<?php

abstract class A {}

final class NewA extends A {}
```

**Note**: if non-`final` classes are needed for mocking in unit tests, simply use [dg/bypass-finals](https://packagist.org/packages/dg/bypass-finals) in your unit tests to allow mocking `final` classes.  

An alternative, not recommended for the [above reasons](#why-this-is-bad), is to make the class part of the public API of your library with `@api`.  