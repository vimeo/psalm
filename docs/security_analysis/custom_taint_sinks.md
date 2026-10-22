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

### Conditional sinks

The taint type can be a conditional type on the parameters, as for [conditional escapes](avoiding_false_positives.md#conditionally-escaping-tainted-input):
each call is a sink of the taints the conditional type resolves to given its arguments.

```php
<?php

final class Renderer {
    public const ESCAPED = 0;
    public const RAW = 1;

    /**
     * @param array{mode?: self::ESCAPED|self::RAW, ...} $options
     * @psalm-taint-sink ($options is array{mode?: self::ESCAPED, ...} ? null : 'html') $content
     */
    public function render(string $content, array $options = []): void {}
}
```

Here `$renderer->render($text)` and `$renderer->render($text, ['mode' => Renderer::ESCAPED])` are not sinks, while
`$renderer->render($text, ['mode' => Renderer::RAW])` is an HTML sink. A condition the arguments don't decide, like a
mode only known at runtime, makes the call a sink of the taints of both branches. A parameter left out takes its
default value.
