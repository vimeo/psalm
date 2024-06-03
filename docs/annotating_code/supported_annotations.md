# Supported docblock annotations

Psalm supports a wide range of docblock annotations.

## PHPDoc tags

Psalm uses the following PHPDoc tags to understand your code:

- [`@var`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/var.html)
  Used for specifying the types of properties and variables
- [`@return`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/return.html)
  Used for specifying the return types of functions, methods and closures
- [`@param`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/param.html)
  Used for specifying types of parameters passed to functions, methods and closures
- [`@property`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/property.html)
  Used to specify what properties can be accessed on an object that uses `__get` and `__set`
- [`@property-read`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/property.html)
  Used to specify what properties can be read on object that uses `__get`
- [`@property-write`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/property.html)
  Used to specify what properties can be written on object that uses `__set`
- [`@method`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/method.html)
  Used to specify which magic methods are available on object that uses `__call`.
- [`@deprecated`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/deprecated.html)
  Used to mark functions, methods, classes and interfaces as being deprecated
- [`@internal`](https://docs.phpdoc.org/latest/guide/references/phpdoc/tags/internal.html)
   Used to mark classes, functions and properties that are internal to an application or library.
- [`@mixin`](#mixins)
    Used to tell Psalm that the current class proxies the methods and properties of the referenced class.

### Off-label usage of the `@var` tag

The `@var` tag is supposed to only be used for properties. Psalm, taking a lead from PHPStorm and other static analysis tools, allows its use inline in the form `@var Type [VariableReference]`.

If `VariableReference` is provided, it should be of the form `$variable` or `$variable->property`. If used above an assignment, Psalm checks whether the `VariableReference` matches the variable being assigned. If they differ, Psalm will assign the `Type` to `VariableReference` and use it in the expression below.

If no `VariableReference` is given, the annotation tells Psalm that the right-hand side of the expression, whether an assignment or a return, is of type `Type`.

```php
<?php
/** @var string */
$a = $_GET['foo'];

/** @var string $b */
$b = $_GET['bar'];

function bat(): string {
    /** @var string */
    return $_GET['bat'];
}
```
### @mixins

Adding `@mixin` to a classes docblock tells Psalm that the class proxies will proxy the methods and properties of the referenced class.

```php
class A
{
    public string $a = 'A';
 
    public function doA(): void
    {
    }
}

/**
 * @mixin A
 */
class B
{
    public string $b = 'B';

    public function doB(): void
    {
    }

    public function __call($name, $arguments)
    {
        (new A())->$name(...$arguments);
    }
    
    public function __get($name)
    {
        (new A())->$name;
    }
}

$b = new B();
$b->doB();
$b->doA(); // works
echo $b->b;
echo $b->a; // works
```


## Psalm-specific tags

There are a number of custom tags that determine how Psalm treats your code.

### `@psalm-consistent-constructor`

See [UnsafeInstantiation](../running_psalm/issues/UnsafeInstantiation.md)

### `@psalm-consistent-templates`

See [UnsafeGenericInstantiation](../running_psalm/issues/UnsafeGenericInstantiation.md)

### `@param-out`, `@psalm-param-out`

This is used to specify that a by-ref type is different from the one that entered. In the function below the first param can be null, but once the function has executed the by-ref value is not null.

```php
<?php
/**
 * @param-out string $s
 */
function addFoo(?string &$s) : void {
    if ($s === null) {
        $s = "hello";
    }
    $s .= "foo";
}
```

### `@psalm-var`, `@psalm-param`, `@psalm-return`, `@psalm-property`, `@psalm-property-read`, `@psalm-property-write`, `@psalm-method`

When specifying types in a format not supported by phpDocumentor ([but supported by Psalm](#type-syntax)) you may wish to prepend `@psalm-` to the PHPDoc tag, so as to avoid confusing your IDE. If a `@psalm`-prefixed tag is given, Psalm will use it in place of its non-prefixed counterpart.

### `@psalm-ignore-var`

This annotation is used to ignore the `@var` annotation written in the same docblock. Some IDEs don't fully understand complex types like generics. To take advantage of such IDE's auto-completion, you may sometimes want to use explicit `@var` annotations even when psalm can infer the type just fine. This weakens the effectiveness of type checking in many cases since the explicit `@var` annotation overrides the types inferred by psalm. As psalm ignores the `@var` annotation which is co-located with `@psalm-ignore-var`, IDEs can use the type specified by the `@var` for auto-completion, while psalm can still use its own inferred type for type checking.

```php
<?php
/** @return iterable<array-key,\DateTime> $f */
function getTimes(int $n): iterable {
    while ($n--) {
        yield new \DateTime();
    }
};
/**
 * @var \Datetime[] $times
 * @psalm-ignore-var
 */
$times = getTimes(3);
// this trace shows "iterable<array-key, DateTime>" instead of "array<array-key, Datetime>"
/** @psalm-trace $times */
foreach ($times as $time) {
    echo $time->format('Y-m-d H:i:s.u') . PHP_EOL;
}
```

### `@psalm-suppress SomeIssueName`

This annotation is used to suppress issues. It can be used in function docblocks, class docblocks and also inline, applying to the following statement.

Function docblock example:

```php
<?php
/**
 * @psalm-suppress PossiblyNullOperand
 */
function addString(?string $s) {
    echo "hello " . $s;
}
```

Inline example:

```php
<?php
function addString(?string $s) {
    /** @psalm-suppress PossiblyNullOperand */
    echo "hello " . $s;
}
```

`@psalm-suppress all` can be used to suppress all issues instead of listing them individually.

### `@psalm-assert`, `@psalm-assert-if-true`, `@psalm-assert-if-false`, `@psalm-if-this-is` and `@psalm-this-out`

See [Adding assertions](adding_assertions.md).

### `@psalm-ignore-nullable-return`

This can be used to tell Psalm not to worry if a function/method returns null. It’s a bit of a hack, but occasionally useful for scenarios where you either have a very high confidence of a non-null value, or some other function guarantees a non-null value for that particular code path.

```php
<?php
class Foo {}
function takesFoo(Foo $f): void {}

/** @psalm-ignore-nullable-return */
function getFoo(): ?Foo {
  return rand(0, 10000) > 1 ? new Foo() : null;
}

takesFoo(getFoo());
```

### `@psalm-ignore-falsable-return`

This provides the same, but for `false`. Psalm uses this internally for functions like `preg_replace`, which can return false if the given input has encoding errors, but where 99.9% of the time the function operates as expected.

### `@psalm-seal-properties`, `@psalm-no-seal-properties`

If you have a magic property getter/setter, you can use `@psalm-seal-properties` to instruct Psalm to disallow getting and setting any properties not contained in a list of `@property` (or `@property-read`/`@property-write`) annotations.
This is automatically enabled with the configuration option `sealAllProperties` and can be disabled for a class with `@psalm-no-seal-properties`

```php
<?php
/**
 * @property string $foo
 * @psalm-seal-properties
 */
class A {
     public function __get(string $name): ?string {
          if ($name === "foo") {
               return "hello";
          }
     }

     public function __set(string $name, $value): void {}
}

$a = new A();
$a->bar = 5; // this call fails
```

### `@psalm-seal-methods`, `@psalm-no-seal-methods`

If you have a magic method caller, you can use `@psalm-seal-methods` to instruct Psalm to disallow calling any methods not contained in a list of `@method` annotations.
This is automatically enabled with the configuration option `sealAllMethods` and can be disabled for a class with `@psalm-no-seal-methods`

```php
<?php
/**
 * @method foo(): string
 * @psalm-seal-methods
 */
class A {
     public function __call(string $name, array $args) {
          if ($name === "foo") {
               return "hello";
          }
     }
 }

$a = new A();
$b = $a->bar(); // this call fails
```

### `@psalm-internal`

Used to mark a class, property or function as internal to a given namespace or class or even method. 
Psalm treats this slightly differently to the PHPDoc `@internal` tag. For `@internal`,
an issue is raised if the calling code is in a namespace completely unrelated to the namespace of the calling code,
i.e. not sharing the first element of the namespace.

In contrast for `@psalm-internal`, the docblock line must specify a namespace. An issue is raised if the calling code
is not within the given namespace.

```php
<?php
namespace A\B {
    /**
    * @internal
    * @psalm-internal A\B
    */
    class Foo { }
}

namespace A\B\C {
    class Bat {
        public function batBat(): void {
            $a = new \A\B\Foo(); // this is fine
        }
    }
}

namespace A {
    class B {
        public function batBat(): void {
            $a = new \A\B\Foo(); // this is fine
        }
    }
}

namespace A\C {
    class Bat {
        public function batBat(): void {
            $a = new \A\B\Foo(); // error
        }
    }
}

namespace X {
    class Foo {        
        /**
         * @psalm-internal Y\Bat::batBat
         */
        public static function barBar(): void {
        }
    }
}

namespace Y {
    class Bat {
        public function batBat() : void {
            \X\Foo::barBar(); // this is fine
        }
        public function fooFoo(): void {
            \X\Foo::barBar(); // error
        }
    }
}
```

### `@psalm-readonly` and `@readonly`

Used to annotate a property that can only be written to in its defining class's constructor.

```php
<?php
class B {
  /** @readonly */
  public string $s;

  public function __construct(string $s) {
    $this->s = $s;
  }
}

$b = new B("hello");
echo $b->s;
$b->s = "boo"; // disallowed
```

### `@psalm-mutation-free`

Used to annotate a class method that does not mutate state, either internally or externally of the class's scope.
This requires that the return value depend only on the instance's properties. For example, `random_int` is considered
mutating here because it mutates the random number generator's internal state.

```php
<?php
class D {
  private string $s;

  public function __construct(string $s) {
    $this->s = $s;
  }

  /**
   * @psalm-mutation-free
   */
  public function getShort() : string {
    return substr($this->s, 0, 5);
  }

  /**
   * @psalm-mutation-free
   */
  public function getShortMutating() : string {
    $this->s .= "hello"; // this is a bug
    return substr($this->s, 0, 5);
  }
}
```

### `@psalm-external-mutation-free`

Used to annotate a class method that does not mutate state externally of the class's scope.

```php
<?php
class E {
  private string $s;

  public function __construct(string $s) {
    $this->s = $s;
  }

  /**
   * @psalm-external-mutation-free
   */
  public function getShortMutating() : string {
    $this->s .= "hello"; // this is fine
    return substr($this->s, 0, 5);
  }

  /**
   * @psalm-external-mutation-free
   */
  public function save() : void {
    file_put_contents("foo.txt", $this->s); // this is a bug
  }
}
```

### `@psalm-immutable`

Used to annotate a class where every property is treated by consumers as `@psalm-readonly` and every instance method is treated as `@psalm-mutation-free`.

```php
<?php
/**
 * @psalm-immutable
 */
abstract class Foo
{
    public string $baz;

    abstract public function bar(): int;
}

/**
 * @psalm-immutable
 */
final class ChildClass extends Foo
{
    public function __construct(string $baz)
    {
        $this->baz = $baz;
    }

    public function bar(): int
    {
        return 0;
    }
}

$anonymous = new /** @psalm-immutable */ class extends Foo
{
    public string $baz = "B";

    public function bar(): int
    {
        return 1;
    }
};
```

### `@psalm-pure`

Used to annotate a [pure function](https://en.wikipedia.org/wiki/Pure_function) - one whose output is just a function of its input.

```php
<?php
class Arithmetic {
  /** @psalm-pure */
  public static function add(int $left, int $right) : int {
    return $left + $right;
  }

  /** @psalm-pure - this is wrong */
  public static function addCumulative(int $left) : int {
    /** @var int */
    static $i = 0; // this is a side effect, and thus a bug
    $i += $left;
    return $i;
  }
}

echo Arithmetic::add(40, 2);
echo Arithmetic::add(40, 2); // same value is emitted

echo Arithmetic::addCumulative(3); // outputs 3
echo Arithmetic::addCumulative(3); // outputs 6
```

On the other hand, `pure-callable` can be used to denote a callable which needs to be pure.

```php
/**
 * @param pure-callable(mixed): int $callback
 */
function foo(callable $callback) {...}

// this fails since random_int is not pure
foo(
    /** @param mixed $p */
    fn($p) => random_int(1, 2)
);
```

### `@psalm-allow-private-mutation`

Used to annotate readonly properties that can be mutated in a private context. With this, public properties can be read from another class but only be mutated within a method of its own class.

```php
<?php
class Counter {
  /**
   * @readonly
   * @psalm-allow-private-mutation
   */
  public int $count = 0;

  public function increment() : void {
    $this->count++;
  }
}

$counter = new Counter();
echo $counter->count; // outputs 0
$counter->increment(); // Method can mutate property
echo $counter->count; // outputs 1
$counter->count = 5; // This will fail, as it's mutating a property directly
```

### `@psalm-readonly-allow-private-mutation`

This is a shorthand for the property annotations `@readonly` and `@psalm-allow-private-mutation`.

```php
<?php
class Counter {
  /**
   * @psalm-readonly-allow-private-mutation
   */
  public int $count = 0;

  public function increment() : void {
    $this->count++;
  }
}

$counter = new Counter();
echo $counter->count; // outputs 0
$counter->increment(); // Method can mutate property
echo $counter->count; // outputs 1
$counter->count = 5; // This will fail, as it's mutating a property directly
```

### `@psalm-trace`

You can use this annotation to trace inferred type (applied to the *next* statement).

```php
<?php

/** @psalm-trace $username */
$username = $_GET['username']; // prints something like "test.php:4 $username: mixed"

```

*Note*: it throws [special low-level issue](../running_psalm/issues/Trace.md).
To see it, you can set the global `errorLevel` to 1, or invoke Psalm with
`--show-info=true`, but both these solutions will probably result in a lot of
output. Another solution is to selectively bump the error level of the issue,
so that you only get one more error:

```xml
<!-- psalm.xml -->
<issueHandlers>
  <Trace errorLevel="error"/>
</issueHandlers>
```

### `@psalm-check-type`

You can use this annotation to ensure the inferred type matches what you expect.

```php
<?php

/** @psalm-check-type $foo = int */
$foo = 1; // No issue

/** @psalm-check-type $bar = int */
$bar = "not-an-int"; // Checked variable $bar = int does not match $bar = 'not-an-int'
```

### `@psalm-check-type-exact`

Like `@psalm-check-type`, but checks the exact type of the variable without allowing subtypes.

```php
<?php

/** @psalm-check-type-exact $foo = int */
$foo = 1; // Checked variable $foo = int does not match $foo = 1
```

### `@psalm-taint-*`

See [Security Analysis annotations](../security_analysis/annotations.md).

### `@psalm-type`

This allows you to define an alias for another type.

```php
<?php
/**
 * @psalm-type PhoneType = array{phone: string}
 */
class Phone {
    /**
     * @psalm-return PhoneType
     */
    public function toArray(): array {
        return ["phone" => "Nokia"];
    }
}
```

### `@psalm-import-type`

You can use this annotation to import a type defined with [`@psalm-type`](#psalm-type) if it was defined somewhere else.

```php
<?php
/**
 * @psalm-import-type PhoneType from Phone
 */
class User {
    /**
     * @psalm-return PhoneType
     */
    public function toArray(): array {
        return array_merge([], (new Phone())->toArray());
    }
}
```

You can also alias a type when you import it:

```php
<?php
/**
 * @psalm-import-type PhoneType from Phone as MyPhoneTypeAlias
 */
class User {
    /**
     * @psalm-return MyPhoneTypeAlias
     */
    public function toArray(): array {
        return array_merge([], (new Phone())->toArray());
    }
}
```

### `@psalm-require-extends`

The `@psalm-require-extends` annotation allows you to define the requirements that a trait imposes on the using class.

```php
<?php
abstract class DatabaseModel {
  // methods, properties, etc.
}

/**
 * @psalm-require-extends DatabaseModel
 */
trait SoftDeletingTrait {
  // useful but scoped functionality, that depends on methods/properties from DatabaseModel
}


class MyModel extends DatabaseModel {
  // valid
  use SoftDeletingTrait;
}

class NormalClass {
  // triggers an error
  use SoftDeletingTrait;
}
```

### `@psalm-require-implements`

Behaves the same way as `@psalm-require-extends`, but for interfaces.

### `@no-named-arguments`

This will prevent access to the function or method tagged with named parameters (by emitting a `NamedArgumentNotAllowed` issue).

Incidentally, it will change the inferred type for the following code:
```php
<?php
    function a(int ...$a){
        var_dump($a);
    }
```
The type of `$a` is `array<array-key, int>` without `@no-named-arguments` but becomes `list<int>` with it, because it excludes the case where the offset would be a string with the name of the parameter

### `@psalm-ignore-variable-property` and `@psalm-ignore-variable-method`

Instructs Psalm to ignore variable property fetch / variable method call when looking for dead code.
```php
class Foo
{
    // this property can be deleted by Psalter,
    // as potential reference in get() is ignored
    public string $bar = 'bar';

    public function get(string $name): mixed
    {
        /** @psalm-ignore-variable-property */
        return $this->{$name};
    }
}
```
When Psalm encounters variable property, it treats all properties in given class as potentially referenced.
With `@psalm-ignore-variable-property` annotation, this reference is ignored.

While `PossiblyUnusedProperty` would be emitted in both cases, using `@psalm-ignore-variable-property`
would allow [Psalter](../manipulating_code/fixing.md) to delete `Foo::$bar`.

`@psalm-ignore-variable-method` behaves the same way, but for variable method calls.

### `@psalm-yield`

Used to specify the type of value which will be sent back to a generator when an annotated object instance is yielded.

```php
<?php
/**
 * @template-covariant TValue
 * @psalm-yield TValue
 */
interface Promise {}

/**
 * @template-covariant TValue
 * @template-implements Promise<TValue>
 */
class Success implements Promise {
    /**
     * @psalm-param TValue $value
     */
    public function __construct($value) {}
}

/**
 * @return Promise<string>
 */
function fetch(): Promise {
    return new Success('{"data":[]}');
}

function (): Generator {
    $data = yield fetch();
    
    // this is fine, Psalm knows that $data is a string
    return json_decode($data);
};
```
This annotation supports only generic types, meaning that e.g. `@psalm-yield string` would be ignored.

### `@api`, `@psalm-api`

Used to tell Psalm that a class or method is used, even if no references to it can be
found. Unused issues will be suppressed.

For example, in frameworks, controllers are often invoked "magically" without
any explicit references to them in your code. You should mark these classes with
`@psalm-api`.
```php
/**
 * @psalm-api
 */
class UnreferencedClass {}
```

### `@psalm-inheritors`

Used to tell Psalm that a class can only be extended by a certain subset of classes.

For example, 
```php
<?php
/**
 * @psalm-inheritors FooClass|BarClass
 */
class BaseClass {}
class FooClass extends BaseClass {}
class BarClass extends BaseClass {}
class BazClass extends BaseClass {} // this is an error
```

## Type Syntax

Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/latest/guide/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).

A detailed write-up is found in [Typing in Psalm](typing_in_psalm.md)
