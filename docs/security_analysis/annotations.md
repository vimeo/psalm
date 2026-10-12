# Security analysis annotations

## `@psalm-taint-source <taint-type>`

See [Custom taint sources](custom_taint_sources.md#taint-source-annotation).

## `@psalm-taint-sink <taint-type> <param-name>`

See [Custom taint sinks](custom_taint_sinks.md).

## `@psalm-taint-escape <taint-type #conditional>`

See [Escaping tainted output](avoiding_false_positives.md#escaping-tainted-output).

## `@psalm-taint-unescape <taint-type>`

See [Unescaping statements](avoiding_false_negatives.md#unescaping-statements).

## Taint type complements

Wherever these annotations take a `<taint-type>`, `~(<taint-type>|<taint-type>)` (or `~<taint-type>` for one) stands
for every taint but the ones listed: the built-in ones (`user_secret` and `system_secret` too), the custom ones,
including those other annotations only register later. The ones listed may be aliases, like `input`. Write it without
spaces.

```php
<?php // --taint-analysis

/**
 * Taken from the request line, which can't hold a line break: any input but a header injection
 *
 * @psalm-taint-source ~(header|user_secret|system_secret)
 */
function getRequestPath(): string {}
```

The taints the native types of a value can't hold are still removed from it: a `string` returned by
`getRequestPath()` above can't be a NoSQL query.

## `@psalm-taint-specialize`

See [Specializing taints in functions](avoiding_false_positives.md#specializing-taints-in-functions) and [Specializing taints in classes](avoiding_false_positives.md#specializing-taints-in-classes).

## `@psalm-flow [proxy <function-like>] ( <arg>, [ <arg>, ] ) [ -> return ]`

See [Taint Flow](taint_flow.md#optimized-taint-flow)
