# Taint Flow

## Optimized Taint Flow

When dealing with frameworks, keeping track of the data flow might involve different layers
and even other 3rd party components. Using the `@psalm-flow` annotation allows PsalmPHP to
take a shortcut and to make a tainted data flow more explicit.

### Proxy hint

```php
<?php // --taint-analysis
/**
 * @psalm-flow proxy exec($value)
 */
function process(string $value): void {}

process($_GET['malicious'] ?? '');
```

The example above states, that the function `process($value)` is a proxy of the native PHP
function `exec($value)` - which is potentially vulnerable to code execution (`TaintedShell`).

**Examples**

+ `@psalm-flow proxy exec($value)` referencing the global/scoped function `exec`
+ `@psalm-flow proxy MyClass::mySinkMethod($value)` referencing a function/method of the class `MyClass`

### Return value hint

```php
<?php // --taint-analysis
/**
 * @psalm-flow ($value, $items) -> return
 */
function inputOutputHandler(string $value, string ...$items): string
{
    // lots of complicated magic
}

echo inputOutputHandler('first', 'second', $_GET['malicious'] ?? '');
```

The example above states, that the function parameters `$value` and `$items` are reflected
again in the return value. Thus, in case any of the input parameters to the function
`inputOutputHandler` is tainted, then the resulting return value is as well. In this
example `TaintedHtml` would be detected due to using `echo`.

### Combined proxy & return value hint

```php
<?php // --taint-analysis
/**
 * @psalm-flow proxy exec($value)
 * @psalm-flow ($value, $items) -> return
 */
function handleInput(string $value, string ...$items): string
{
    // lots of complicated magic
}

echo handleInput($_GET['malicious'] ?? '');
```

The example above combines both previous examples and shows, that the `@psalm-flow` annotation
can be used multiple times. Here, it would lead to detecting both `TaintedHtml` and `TaintedShell`.
