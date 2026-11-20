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

A parameter of a method implementing or overriding one with a sink is a sink too, unless it has a
`@psalm-taint-sink` of its own:

```php
<?php
interface Runner {
    /** @psalm-taint-sink shell $command */
    public function run(string $command): void;
}

final class LocalRunner implements Runner {
    public function run(string $command): void {}
}

function deploy(LocalRunner $runner): void {
    $runner->run((string) $_GET["command"]); // TaintedShell
}
```

`@psalm-taint-sink none <param-name>` gives such a parameter no sink. A call through `Runner` is still reported:

```php
<?php
interface Runner {
    /** @psalm-taint-sink shell $command */
    public function run(string $command): void;
}

final class EscapingRunner implements Runner {
    /** @psalm-taint-sink none $command */
    public function run(string $command): void {
        exec("run " . escapeshellarg($command));
    }
}

function deploy(EscapingRunner $runner): void {
    $runner->run((string) $_GET["command"]); // no issue
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

Like [conditional escapes](avoiding_false_positives.md#conditionally-escaping-tainted-input), the taint type can be a
conditional type on the parameters. Each call resolves it with its arguments:

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

Here `render($text)` and `render($text, ['mode' => Renderer::ESCAPED])` aren't sinks, while
`render($text, ['mode' => Renderer::RAW])` is an HTML sink. When the mode is only known at runtime, both branches apply.
