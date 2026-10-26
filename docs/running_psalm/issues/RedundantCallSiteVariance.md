# RedundantCallSiteVariance

Emitted when a generic type argument is marked `covariant`, but the template it is given to is declared `@template-covariant` already

```php
<?php
/**
 * @template-covariant T
 */
class Box {}

/**
 * @param Box<covariant int> $box
 */
function takesBox(Box $box): void {}
```

## Fixing

Remove the `covariant` keyword: the template is covariant already. Builtin classes whose templates are covariant (`Iterator`, `Traversable`, `Generator`, …) trigger it as well. `ReflectionClass` is exempt, so that code written for several PHP versions can keep the annotation.
