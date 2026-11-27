# InvalidTemplateParam

Emitted when using the `@extends`/`@implements` annotation to extend a class that has a template type constraint, where that extended value does not satisfy the parent class/interface's constraints.

```php
<?php

/**
 * @template T of object
 */
class Base {}

/** @template-extends Base<int> */
class SpecializedByInheritance extends Base {}
```

Also emitted when a `covariant` or `contravariant` type argument contradicts `@template-covariant` or `@template-contravariant`:

```php
<?php

/** @template-covariant T */
interface Source {}

/** @param Source<contravariant Exception> $source */
function consume(Source $source): void {}
```
