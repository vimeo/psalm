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

For situations where the `callable` needs to be pure or immutable, the subtypes `pure-callable` and `pure-Closure` are also available.

This can be useful when the `callable` is used in a function marked with `@psalm-pure` or `@psalm-mutation-free`, for example:

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
