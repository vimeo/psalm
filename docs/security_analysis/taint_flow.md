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

The methods of the builtin containers and iterators (`ArrayObject`, `ArrayIterator`, `SplObjectStorage`, …) and of
`Generator` return what their object holds without annotations: `getArrayCopy()` all of it, `offsetGet('key')` what
is stored under `'key'`, `current()` an element, `key()` a key, and `Generator::getReturn()` what the generator
returns. Plugins that follow calls themselves (such as the method calls of a template) get the path along which a
method returns what its object holds with `Codebase::getReceiverTaintPath()`: `=`, `arrayvalue-fetch`,
`arrayvalue-fetch-'key'` given the types of the arguments of a call with a literal key, `arraykey-fetch` or `return`,
or null for a method returning none of it.

### Callable result to return value hint

```php
<?php // --taint-analysis
interface Cache
{
    /**
     * @template T
     * @param callable(): T $compute
     * @return T
     * @psalm-taint-specialize
     * @psalm-flow ($compute()) -> return
     */
    public function get(string $key, callable $compute): mixed;
}

function show(Cache $cache): void
{
    echo $cache->get('key', fn(): string => $_GET['malicious'] ?? '');
}
```

`$compute()` in a return value hint states that what the function returns holds what the callables given to `$compute`
return: the closure's body, or the function named. Here `TaintedHtml` is detected. As for `$this`, each call returns
what its own callables return only with `@psalm-taint-specialize`.

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
  `$key`, and `Store::$data[$key] -> return` makes a call return what is stored under its key. `($compute())` stores
  what the callables given to `$compute` return, as in a [return value hint](#callable-result-to-return-value-hint):
  `($compute()) -> Cache::$data[$key]` for a cache computing what it stores when the key is missing.
+ A store is named like a static property, `Class::$name`. The class and the property need not exist; `self` and
  `static` name the class declaring the method. The methods of other classes (a wrapper, an extension) share a store
  by naming the same one.
+ `[*]` is any key: `($values) -> Store::$data[*]` for a method storing an array of keys and values, and
  `Store::$data[*] -> return` for one returning what is stored under any key. `[$keys[*]]` is each key of a list, as
  for `mGet(array $keys)`. In nested stores, like `Store::$data[$namespace][$key]`, only the first key tells the values
  apart.
+ Keys are told apart by what the analysis knows of them: the exact keys of literal strings and class names, else how
  the key starts: the start of a concatenation, of an interpolated string, of a `sprintf()` format up to its first
  conversion other than `%s`, of an `implode()` of a list with a literal separator, `__CLASS__`, or what the variable
  (also one a closure uses), the property of `$this`, the method of the class or the function giving the key starts
  with. A property set only by its default and its constructor starts like what the constructor is given by the `new`
  of the classes analyzed. The keys of a list start like all its items: those of a literal list, those assigned to it
  (`$keys[] = 'lock_' . $id;`), and those of a list a method or a function builds this way and returns. A key the
  analysis knows nothing of can be any key.
+ Psalm's stubs declare the keyed store of phpredis, `Redis::$data`, and that of APCu, `APCu::$data`. Plugins can
  connect their own nodes to a store with `Codebase::addTaintStoreWrite()` (paths from a node into the store under
  the names) and `Codebase::addTaintStoreRead()` (paths from the store to a node), with the names
  `Codebase::getTaintStoreNames()` tells for an expression, for stores whose keys an annotation can't express (a
  table named in an SQL query).

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

## Taint flows from plugins

Plugins can add flows that annotations can't express with these `Codebase` methods (`$source` is the event's
`getStatementsSource()`):

+ `getTaintNode($source, $label, $location, $value_type = null)`: a node for a value at a location. The taints of
  `$value_type` flow into it. To make an expression carry what reaches the node, use
  `$type->addParentNodes([$node->id => $node])`.
+ `getTaintPropertyNode('Class::$name')`: the node holding a property's value. The property doesn't need to be declared.
+ `addTaintSink($taint_id, $location, $taints)`: adds a sink and returns it.
+ `addTaintPath($source, $from, $to, $path_type = '=', $added_taints = 0, $removed_taints = 0)`: connects two nodes.
+ `tracksTaintFlow($source)`: false when taints aren't tracked there, e.g. when `TaintedInput` is suppressed. The
  methods above then add nothing.

See [examples/plugins/TaintModelFields.php](https://github.com/vimeo/psalm/blob/master/examples/plugins/TaintModelFields.php),
which tracks ORM model fields from `set()` to `get()`.
