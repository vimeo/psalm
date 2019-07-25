# Templating

Docblocks allow you to tell Psalm some simple information about how your code works. For example `@return int` in a function return type tells Psalm that a function should return an `int` and `@return MyContainer` tells Psalm that a function should return an instance of a user-defined class `MyContainer`. In either case, Psalm can check that the function actually returns those types _and_ that anything calling that function uses its returned value properly.

Templated types allow you to tell Psalm even more information about how your code works.

Let's look at a simple class `MyContainer`:

```php
class MyContainer {
  private $value;
  
  public function __construct($value) {
    $this->value = $value;
  }
  
  public function getValue() {
    return $this->value;
  }
}
```

When Psalm handles the return type of `$my_container->getValue()` it doesn't know what it's getting out, because the value can be arbitrary.

Templated annotations provide us with a workaround - we can define a generic/templated param `T` that is a placeholder for the value inside `MyContainer`:

```php
/**
 * @template T
 */
class MyContainer {
  /** @var T */
  private $value;
  
  /** @param T $value */
  public function __construct($value) {
    $this->value = $value;
  }
  
  /** @return T */
  public function getValue() {
    return $this->value;
  }
}
```

Now we can substitute values for that templated param when we reference `MyContainer` in docblocks e.g. `@return MyContainer<int>`. This tells Psalm to substitute `T` for `int` when evaluating that return type, effectively treating it as a class that looks like

```php
class One_off_instance_of_MyContainer {
  /** @var int */
  private $value;
  
  /** @param int $value */
  public function __construct($value) {
    $this->value = $value;
  }
  
  /** @return int */
  public function getValue() {
    return $this->value;
  }
}
```

This pattern can be used in large number of different situations like mocking, collections, iterators and loading arbitrary objects. Psalm has a large number of annotations to make it easy to use templated types in your codebase.

## `@template`

The `@template` tag allows classes and functions to declare a generic type parameter.

As a very simple example, this function returns whatever is passed in:

```php
/**
 * @template T
 * @psalm-param T $t
 * @return T
 */
function mirror($t) {
    return $t;
}

$a = 5;
$b = mirror(5); // Psalm knows the result is an int

$c = "foo";
$d = mirror($c); // Psalm knows the result is string
```

Psalm also uses `@template` annotations in its stubbed versions of PHP array functions e.g.

```php
/**
 * Takes one array with keys and another with values and combines them
 *
 * @template TKey
 * @template TValue
 *
 * @param array<mixed, TKey> $arr
 * @param array<mixed, TValue> $arr2
 * @return array<TKey, TValue>
 */
function array_combine(array $arr, array $arr2) {}
```

### Notes
- `@template` tag order matters for class docblocks, as they dictate the order in which those generic parameters are referenced in docblocks.
- The names of your templated types (e.g. `TKey`, `TValue` don't matter outside the scope of the class or function in which they're declared.

## `@param class-string<T>`

Psalm also allows you to parameterise class types

```php
/**
 * @template T
 * @psalm-param class-string<T> $class
 * @return T
 */
function instantiator(string $class) {
    return new $class();
}

class Foo {}

$a = instantiator(Foo::class); // Psalm knows the result is an object of type Foo
```

## Template inheritance

Psalm allows you to extend templated classes with `@extends`/`@template-extends`:

```php
/**
 * @template T
 */
class ParentClass {}

/**
 * @extends ParentClass<int>
 */
class ChildClass extends ParentClass {}
```

similarly you can implement interfaces with `@implements`/`@template-implements`

```php
/**
 * @template T
 */
interface IFoo {}

/**
 * @implements IFoo<int>
 */
class Foo implements IFoo {}
```

and import traits with `@use`/`@template-use`

```php
/**
 * @template T
 */
trait MyTrait {}

class Foo {
    /**
     * @use MyTrait<int>
     */
    use MyTrait;
}
```

You can also extend one templated class with another, e.g.

```php
/**
 * @template T1
 */
class ParentClass {}

/**
 * @template T2
 * @extends ParentClass<T2>
 */
class ChildClass extends ParentClass {}
```

## Template constraints

You can use `@template of <type>` to restrict input. For example, to restrict to a given class you can use

```php
class Foo {}
class FooChild extends Foo {}

/**
 * @template T of Foo
 * @psalm-param T $class
 * @return array<int, T>
 */
function makeArray($t) {
    return [$t];
}
$a = makeArray(new Foo()); // typed as array<int, Foo>
$b = makeArray(new FooChild()); // typed as array<int, FooChild>
$c = makeArray(new stdClass()); // type error
```

Templated types aren't limited to key-value pairs, and you can re-use templates across multiple arguments of a template-supporting type:
```php
/**
 * @template T0 as array-key
 *
 * @template-implements IteratorAggregate<T0, int>
 */
abstract class Foo implements IteratorAggregate {
  /**
   * @var int
   */
  protected $rand_min;

  /**
   * @var int
   */
  protected $rand_max;

  public function __construct(int $rand_min, int $rand_max) {
    $this->rand_min = $rand_min;
    $this->rand_max = $rand_max;
  }

  /**
   * @return Generator<T0, int, mixed, T0>
   */
  public function getIterator() : Generator {
    $j = random_int($this->rand_min, $this->rand_max);
    for($i = $this->rand_min; $i <= $j; $i += 1) {
      yield $this->getFuzzyType($i) => $i ** $i;
    }

    return $this->getFuzzyType($j);
  }

  /**
   * @return T0
   */
  abstract protected function getFuzzyType(int $i);
}

/**
 * @template-extends Foo<int>
 */
class Bar extends Foo {
  protected function getFuzzyType(int $i) : int {
    return $i;
  }
}

/**
 * @template-extends Foo<string>
 */
class Baz extends Foo {
  protected function getFuzzyType(int $i) : string {
    return static::class . '[' . $i . ']';
  }
}
```

## Builtin templated classes and interfaces

Psalm has support for a number of builtin classes and interfaces that you can extend/implement in your own code.

- `interface Traversable<TKey, TValue>`
- `interface ArrayAccess<TKey, TValue>`
- `interface IteratorAggregate<TKey, TValue> extends Traversable<TKey, TValue>`
- `interface Iterator<TKey, TValue> extends Traversable<TKey, TValue>`
- `interface SeekableIterator<TKey, TValue> extends Iterator<TKey, TValue>`

- `class Generator<TKey, TValue, TSend, TReturn> extends Traversable<TKey, TValue>`
- `class ArrayObject<TKey, TValue> implements IteratorAggregate<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class ArrayIterator<TKey of array-key, TValue> implements SeekableIterator<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class DOMNodeList<TNode of DOMNode> implements Traversable<int, TNode>`
- `class SplDoublyLinkedList<TKey, TValue> implements Iterator<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class SplQueue<TValue> extends SplDoublyLinkedList<int, TValue>`
