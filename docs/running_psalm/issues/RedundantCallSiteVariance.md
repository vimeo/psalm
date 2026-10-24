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
