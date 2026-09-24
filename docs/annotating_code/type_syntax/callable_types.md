# Callable types

Psalm supports a special format for `callable`s of the form. It can also be used for annotating `Closure`.

```
callable(Type1, OptionalType2=, SpreadType3...):ReturnType
```

Adding `=` after the type implies it is optional, and suffixing with `...` implies the use of the spread operator.

Using this annotation you can specify that a given function return a `Closure` e.g.

```php
<?php
/**
 * @return Closure(bool):int
 */
function delayedAdd(int $x, int $y) : Closure {
  return function(bool $debug) use ($x, $y) {
    if ($debug) echo "got here" . PHP_EOL;
    return $x + $y;
  };
}

$adder = delayedAdd(3, 4);
echo $adder(true);
```

## Pure callables

A callable type carries the capabilities (see [purity and capabilities](../supported_annotations.md#purity-and-capabilities))
the callable may use, given in angle brackets after the keyword:

* `Closure<pure>(int): int` / `callable<pure>(int): int` - a pure callable, also written `pure-Closure(int): int` / `pure-callable(int): int`
* `Closure<mutation-free>(): int`, `Closure<external-mutation-free>(): void` - the named purity levels
* `Closure<write-props|io>(): void` - any combination of capabilities
* `Closure<P>(): void` - a purity template declared with `@psalm-purity-template`
* `Closure<_>(): void` - in a parameter's type only: the function inherits its purity from that parameter (see [`@psalm-purity-template`](../supported_annotations.md#psalm-purity-template))
* `Closure(): void` / `callable(): void` - an impure callable (the default), also written `impure-Closure(): void` / `impure-callable(): void`

The parameter list may be left out: `Closure<pure>` is any pure closure.

A callable needing fewer capabilities fits where more are allowed: a `pure-Closure` can be passed
for a `Closure<io>` parameter, but not the other way round. A closure's capabilities are inferred
from its body, so its type carries what it actually does.

This can be useful when the `callable` is used in a function marked with `@psalm-pure` or `@psalm-mutation-free` or `@psalm-external-mutation-free`, for example:

```php
<?php
/** @psalm-immutable */
class intList {
    /** @param list<int> $items */
    public function __construct(private array $items) {}
    
    /**
     * @param pure-callable(int, int): int $callback
     * @psalm-mutation-free
     */
    public function walk(callable $callback): int {
        return array_reduce($this->items, $callback, 0);
    }
}

$list = new intList([1,2,3]);

// This is ok, as the callable is pure
echo $list->walk(fn (int $c, int $v): int => $c + $v);

// This will cause an InvalidArgument error, as the closure calls an impure function
echo $list->walk(fn (int $c, int $v): int => $c + random_int(1, $v));
```
