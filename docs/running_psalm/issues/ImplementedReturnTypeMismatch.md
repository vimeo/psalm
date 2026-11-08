# ImplementedReturnTypeMismatch

Emitted when a class that inherits another, or implements an interface, has a docblock return type that's entirely different to the parent. 

```php
<?php

class A {
    /** @return bool */
    public function foo() {
        return true;
    }
}
class B extends A {
    /** @return string */
    public function foo()  {
        return "hello";
    }
}
```

A method without a return type inherits those of the methods it overrides, so they need a common subtype:

```php
<?php

interface Renderer {
    /** @return string */
    public function render();
}
interface Counter {
    /** @return int */
    public function render();
}
final class Widget implements Renderer, Counter {
    public function render() { // ImplementedReturnTypeMismatch, calls return int|string
        return "hello";
    }
}
```

## How to fix

Make sure to respect the [Liskov substitution principle](https://en.wikipedia.org/wiki/Liskov_substitution_principle) – any method that overrides a parent method must return a subtype of the parent method.

In the above case, that means adding the child return type to the parent one.

```php
<?php

class A {
    /** @return bool|string */
    public function foo() {
        return true;
    }
}
class B extends A {
    /** @return string */
    public function foo()  {
        return "hello";
    }
}
```
