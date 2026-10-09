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

### Object to return value hint

```php
<?php // --taint-analysis
interface Message
{
    /**
     * @psalm-taint-specialize
     * @psalm-flow ($this) -> return
     */
    public function getBody(): string;
}

/**
 * @psalm-taint-source input
 */
function fetch(): Message {
    // a request to another server
}

echo fetch()->getBody();
```

`$this` in a return value hint states that what a method returns holds what the object it is called on holds: here,
the bodies of the messages `fetch()` returns are tainted, while those of the messages the code builds itself only hold
what they were given. This is useful for methods whose bodies Psalm doesn't analyze, such as those of interfaces,
stubs and vendor code. With `@psalm-taint-specialize`, each call returns what its own object holds; without it, what
any object the method is called on holds flows into what every call returns.

### Keyed stores

Caches, key-value stores and similar services keep what they are given out of the analyzed code, under a key. Their
methods can say so, and what is stored under a key then flows into what the reads of that key return, wherever the
code reading it is:

```php
<?php // --taint-analysis
final class Cache
{
    /**
     * @psalm-flow ($value) -> Cache::$data[$key]
     */
    public static function set(string $key, string $value): void {}

    /**
     * @psalm-flow Cache::$data[$key] -> return
     */
    public static function get(string $key): string {}
}

function remember(string $id): void {
    Cache::set('comment_' . $id, $_GET['comment'] ?? '');
}

echo Cache::get('comment_1'); // TaintedHtml
echo Cache::get('title_1');   // no issue
```

+ `($value, $other) -> Store::$data[$key]` stores the arguments given to `$value` and `$other` under the key given to
  `$key`, and `Store::$data[$key] -> return` makes a call return what is stored under its key.
+ A store is named like a static property, `Class::$name`. The class and the property need not exist; `self` and
  `static` name the class declaring the method. The methods of other classes (a wrapper, an extension) share a store
  by naming the same one.
+ `[*]` is any key: `($values) -> Store::$data[*]` for a method storing an array of keys and values, and
  `Store::$data[*] -> return` for one returning what is stored under any key. `[$keys[*]]` is each key of a list, as
  for `mGet(array $keys)`. In nested stores, like `Store::$data[$namespace][$key]`, only the first key tells the values
  apart.
+ Keys are told apart by what the analysis knows of them: the exact keys of literal strings and class names, else how
  the key starts: the start of a concatenation, of an interpolated string, or of a `sprintf()` format up to its first
  conversion other than `%s`, `__CLASS__`, or what the variable, the property of `$this` or the method of the class
  giving the key starts with. A property set only by its default and its constructor starts like what the constructor
  is given by the `new` of the classes analyzed. A key the analysis knows nothing of can be any key.
+ Psalm's stubs declare the keyed store of phpredis, `Redis::$data`, and that of APCu, `APCu::$data`. Plugins can
  write into a store and read from it with `Codebase::addTaintStoreWrite()`, `Codebase::addTaintStoreRead()` and
  `Codebase::getTaintStoreNames()`, for stores whose keys an annotation can't express (a table named in an SQL query).

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
