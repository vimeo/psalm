# Security analysis annotations

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
