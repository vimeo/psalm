# Security analysis annotations

## `@psalm-taint-source <taint-type> [return['key']]`

See [Custom taint sources](custom_taint_sources.md#taint-source-annotation).

## `@psalm-taint-sink <taint-type #conditional> <param-name>`

See [Custom taint sinks](custom_taint_sinks.md) and [Conditional sinks](custom_taint_sinks.md#conditional-sinks).

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

## `@psalm-cli-only`

See [Output of command-line code](avoiding_false_positives.md#output-of-command-line-code).

## `@psalm-taint-specialize`

See [Specializing taints in functions](avoiding_false_positives.md#specializing-taints-in-functions) and [Specializing taints in classes](avoiding_false_positives.md#specializing-taints-in-classes).

## `@psalm-flow [proxy <function-like>] ( <arg>, [ <arg>, ] ) [ -> return ]`

See [Taint Flow](taint_flow.md#optimized-taint-flow).

Each `<arg>` is a parameter, like `$value`. With `-> return`, an `<arg>` can also be:

- `$this`: the object the method is called on
- a parameter followed by `()`, like `$compute()`: what the callables passed to that parameter return

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> <Class>::$<store>[<key>]` and `@psalm-flow <Class>::$<store>[<key>] -> return`

See [Keyed stores](taint_flow.md#keyed-stores)

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $this-><name>` and `@psalm-flow $this-><name> -> return`

`<name>` is a parameter giving the name of the property, like `$name`, or `*` for any property.
See [Properties set and got by name](taint_flow.md#properties-set-and-got-by-name)

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $this->{key}` and `@psalm-flow $this->{key} -> return[key]`

Each property is set to, or returned under, its name as a key of the array.
See [Properties set and got by name](taint_flow.md#properties-set-and-got-by-name)

## `@psalm-flow ( <arg>, [ <arg>, ] ) -> $this-><property>[<key>]` and `@psalm-flow $this-><property>[<key>] -> return`

`<property>` is the name of the property, like `vars`. The optional `[<key>]` is the element of that array property
under the key given to a parameter, like `[$name]`, or `[*]` for any element.
See [A property and its elements](taint_flow.md#a-property-and-its-elements)
