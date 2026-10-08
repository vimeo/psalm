# Custom Taint Sinks

The `@psalm-taint-sink <taint-type> <param-name>` annotation allows you to define a taint sink.

Any tainted value matching the given [taint type](index.md#taint-types) will be reported as an error by Psalm.

### Example

Here the `PDOWrapper` class has an `exec` method that should not receive tainted SQL, so we can prevent its insertion:

```php
<?php

class PDOWrapper {
    /**
     * @psalm-taint-sink sql $sql
     */
    public function exec(string $sql) : void {}
}
```

### Values of an array

Append `[*]` to the parameter name to make only the values of the array given to the parameter a sink, not its keys:

```php
<?php

final class TemplateLoader {
    /**
     * @param array<string, string> $templates template names => template sources
     * @psalm-taint-sink eval $templates[*]
     */
    public function __construct(private array $templates) {}
}
```

Here a tainted template name is not reported, a tainted template source is.
