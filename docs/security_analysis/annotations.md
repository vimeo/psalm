# Security analysis annotations

A method overriding or implementing another one has the annotations of the method it overrides, as it has its
docblock types, in three parts, each inherited unless the method declares that part itself:

- its taint sources (`@psalm-taint-source`);
- the taint sinks of its parameters (`@psalm-taint-sink`, `@psalm-assert-untainted`), matched by their position;
- its flows (`@psalm-flow`, `@psalm-taint-escape`, `@psalm-taint-unescape`).

`@psalm-taint-specialize` is not inherited.

## `@psalm-no-taint-source`, `@psalm-no-taint-sink`, `@psalm-no-flow`

An override opts out of the taint sources, the taint sinks or the flows of the method it overrides: it is no taint
source, its parameters are no taint sinks, or taints flow through it as through its body. The other parts are still
inherited, and the methods overriding it inherit the opt-out.

```php
<?php

class Request {
    /** @psalm-taint-source input */
    public function get(string $name): string {
        return (string) ($_GET[$name] ?? '');
    }
}

final class QueuedRequest extends Request {
    /** @param array<string, string> $vars */
    public function __construct(private array $vars) {}

    /**
     * The variables of a queued request come from the code queueing it, not from a client
     *
     * @psalm-no-taint-source
     */
    public function get(string $name): string {
        return $this->vars[$name] ?? '';
    }
}
```

## `@psalm-taint-source <taint-type>`

See [Custom taint sources](custom_taint_sources.md#taint-source-annotation).

## `@psalm-taint-sink <taint-type> <param-name>`

See [Custom taint sinks](custom_taint_sinks.md).

## `@psalm-taint-escape <taint-type #conditional>`

See [Escaping tainted output](avoiding_false_positives.md#escaping-tainted-output).

## `@psalm-taint-unescape <taint-type>`

See [Unescaping statements](avoiding_false_negatives.md#unescaping-statements).

## `@psalm-taint-specialize`

See [Specializing taints in functions](avoiding_false_positives.md#specializing-taints-in-functions) and [Specializing taints in classes](avoiding_false_positives.md#specializing-taints-in-classes).

## `@psalm-flow [proxy <function-like>] ( <arg>, [ <arg>, ] ) [ -> return ]`

See [Taint Flow](taint_flow.md#optimized-taint-flow)

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> <Class>::$<store>[<key>]` and `@psalm-flow <Class>::$<store>[<key>] -> return`

See [Keyed stores](taint_flow.md#keyed-stores)
