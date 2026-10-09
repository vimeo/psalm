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

### Template parameters of functions without an analyzed body

When the body of a function or method isn't analyzed (it is outside the project directories, declared in a stub or
abstract, or a method of an interface), what a call returns holds what the arguments binding the template parameters
of its return type hold, without any annotation. For a callable parameter, that is what the callables given return:

```php
<?php // --taint-analysis
interface Cache
{
    /**
     * @template T
     * @param callable(): T $compute
     * @return T
     */
    public function get(string $key, callable $compute): mixed;
}

function show(Cache $cache): void
{
    echo $cache->get('key', fn(): string => $_GET['malicious'] ?? '');
}
```

Here `TaintedHtml` is detected: what the closure returns binds `T`, which `get()` returns. Each call returns only what
its own arguments bind, and the calls of functions whose body is analyzed keep the flow of their body.
