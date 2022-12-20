# PossiblyNullArgument

Emitted when calling a function with a value that’s possibly null when the function does not expect it

```php
<?php

function foo(string $s): void {}
foo(rand(0, 1) ? "hello" : null);
```

## Common Problem Cases

### Using a Function Call inside `if`

```php
<?php

if (is_string($cat->getName()) {
    foo($cat->getName());
}
```
This fails since it's not guaranteed that subsequent calls to `$cat->getName()` always give the same result.

#### Possible Solutions

Use a variable:
```php
<?php

$catName = $cat->getName();
if (is_string($catName) {
    foo($catName);
}
unset($catName);
```

Or add [`@psalm-mutation-free`](../../annotating_code/supported_annotations.md#psalm-mutation-free) to the declaration of the function

### Calling Another Function After `if`

```php
<?php

if (is_string($cat->getName()) {
    changeCat();
    foo($cat->getName());
}
```
This fails since psalm cannot know if `changeCat()` does actually modify `$cat`.

#### Possible Solutions

* Add [`@psalm-mutation-free`](../../annotating_code/supported_annotations.md#psalm-mutation-free) to the declaration of the other function (here: `changeCat()`) too
* Use a variable: See above
