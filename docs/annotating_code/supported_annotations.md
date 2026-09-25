# Supported docblock annotations

Psalm supports a wide range of docblock annotations.

## PHPDoc tags

Psalm uses the following PHPDoc tags to understand your code:

- [`@var`](https://docs.phpdoc.org/guide/references/phpdoc/tags/var.html)
  Used for specifying the types of properties and variables
- [`@return`](https://docs.phpdoc.org/guide/references/phpdoc/tags/return.html)
  Used for specifying the return types of functions, methods and closures
- [`@param`](https://docs.phpdoc.org/guide/references/phpdoc/tags/param.html)
  Used for specifying types of parameters passed to functions, methods and closures
- [`@property`](https://docs.phpdoc.org/guide/references/phpdoc/tags/property.html)
  Used to specify what properties can be accessed on an object that uses `__get` and `__set`
- [`@property-read`](https://docs.phpdoc.org/guide/references/phpdoc/tags/property.html)
  Used to specify what properties can be read on object that uses `__get`
- [`@property-write`](https://docs.phpdoc.org/guide/references/phpdoc/tags/property.html)
  Used to specify what properties can be written on object that uses `__set`
- [`@method`](https://docs.phpdoc.org/guide/references/phpdoc/tags/method.html)
  Used to specify which magic methods are available on object that uses `__call`.
- [`@deprecated`](https://docs.phpdoc.org/guide/references/phpdoc/tags/deprecated.html)
  Used to mark functions, methods, classes and interfaces as being deprecated
- [`@internal`](https://docs.phpdoc.org/guide/references/phpdoc/tags/internal.html)
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

Suppressing an unused-code issue (such as `UnusedClass` or `PossiblyUnusedMethod`) only silences the report for that symbol — it does not mark the symbol as used, so code that is only referenced from it is still reported as unused. Redundant `@psalm-suppress` annotations, in any docblock (including on classes, interfaces, traits and enums), are reported by [`--find-unused-psalm-suppress`](../running_psalm/issues/UnusedPsalmSuppress.md).

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

### `@psalm-seal-properties`, `@psalm-no-seal-properties`, `@seal-properties`, `@no-seal-properties`

If you have a magic property getter/setter, you can use `@psalm-seal-properties` to instruct Psalm to disallow getting and setting any properties not contained in a list of `@property` (or `@property-read`/`@property-write`) annotations.
This is automatically enabled with the configuration option `sealAllProperties` and can be disabled for a class with `@psalm-no-seal-properties`

```php
<?php
/**
 * @property string $foo
 * @seal-properties
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

### `@psalm-seal-methods`, `@psalm-no-seal-methods`, `@seal-methods`, `@no-seal-methods`

If you have a magic method caller, you can use `@psalm-seal-methods` to instruct Psalm to disallow calling any methods not contained in a list of `@method` annotations.
This is automatically enabled with the configuration option `sealAllMethods` and can be disabled for a class with `@psalm-no-seal-methods`

```php
<?php
/**
 * @method foo(): string
 * @seal-methods
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

### Purity and capabilities

Psalm tracks which side effects a function, method or closure may have as a set of
**capabilities**. A function-like may only perform an operation, or call another function-like,
when its own capabilities include every capability the operation or callee requires. Code without
a `@psalm-capabilities` annotation has every capability.

| Capability         | Allows                                                                                                 |
|--------------------|--------------------------------------------------------------------------------------------------------|
| `read-props`       | reading instance properties of mutable objects, including `$this` (immutable objects never need it)    |
| `write-this-props` | writing or unsetting properties of `$this` (implies `read-props`)                                      |
| `write-props`      | writing or unsetting properties of any object (implies `write-this-props`)                             |
| `read-globals`     | reading static properties, superglobals and binding `global` variables (the values reached this way can only be mutated with `write-globals`) |
| `write-globals`    | writing them, including through a bound `global` variable, and using `static` variables (implies `read-globals`) |
| `write-refs`       | writing through by-reference parameters and other references into another scope                        |
| `io`               | `echo`, `print`, `exit` with a message, and the builtin functions with side effects (`time`, `random_int`, `file_put_contents`, …); the builtins touching process-wide state (`mt_rand`, `ini_set`, `spl_autoload_register`, …) need `write-globals` instead |

Two names stand for the extremes: `pure` is the empty set, a
[pure function](https://en.wikipedia.org/wiki/Pure_function) whose result depends only on its
arguments, and `impure` is every capability. They have annotations of their own, `@psalm-pure`
and `@psalm-impure`.

Values reached from global state stay bound to it: an object read from a static property, a
superglobal or a `global` variable, returned by a function that may read globals, or fetched from
such an object, can only have its properties written, its mutating methods called, or be passed
to a function that may write properties, by code that has `write-globals`. This is what makes
`read-globals` a read-only view of global state, like Hack's `readonly` values:

```php
<?php
final class Config {
    public string $env = "prod";
    public static ?Config $instance = null;
}

/** @psalm-capabilities read-globals */
function config(): ?Config {
    return Config::$instance;
}

/** @psalm-capabilities read-globals|write-props */
function tamper(): void {
    $c = config();
    if ($c !== null) {
        $c->env = "dev"; // error: mutating an object reached from global state requires write-globals
    }
}
```

### `@psalm-capabilities`

`@psalm-capabilities` gives a function, method or closure the capabilities it may use, separated
by commas or `|`. `@psalm-pure` gives it none, and `@psalm-impure` every capability (the default,
spelled out):

```php
<?php
final class Counter {
    public static int $count = 0;
}

/** @psalm-pure */
function add(int $left, int $right): int {
    return $left + $right;
}

/** @psalm-capabilities read-globals */
function currentCount(): int {
    return Counter::$count;
}

/** @psalm-capabilities write-globals */
function increment(): int {
    Counter::$count++;
    return currentCount(); // ok: write-globals includes read-globals
}

/** @psalm-capabilities write-props */
function reset(Counter $c): void {
    increment(); // error: write-props does not include write-globals
    echo "reset"; // error: io is required
}
```

On a class, it applies to every method of the class. `@psalm-pure` on a class also
bans the use of properties.

Abstract methods, and the methods of interfaces, have no body to infer their capabilities from,
so Psalm asks for them to be annotated explicitly (use `@psalm-impure` for one that
may do anything). A method may need fewer capabilities than the method it overrides, never more.

A capability set can be named once with a type alias and used in `@psalm-capabilities` and in
closure types, like Hack's context constants; aliases of other classes are imported with
`@psalm-import-type`:

```php
<?php
/** @psalm-type Storage = write-props|io */
final class Repo {
    /** @psalm-capabilities Storage */
    public function save(): void { echo "saved"; }
}

/** @psalm-import-type Storage from Repo */
final class Service {
    /**
     * @psalm-capabilities Storage
     * @param Closure<Storage>(): void $after
     */
    public function run(Repo $repo, Closure $after): void {
        $repo->save();
        $after();
    }
}
```

Everything a function-like does is checked, including what happens implicitly: `clone` calls
`__clone`, string interpolation and casts call `__toString`, `$object()` calls `__invoke`, array
access on objects calls the `ArrayAccess` methods, `throw new` and `new $className` call the
constructor (an unknown class may do anything), and `foreach` over an object calls its `Iterator`
methods (`rewind`, `valid`, `current`, `key`, `next`), or `getIterator()` and then the methods of
the iterator it returns, which counts as freshly created (see
[iterators and generators](#iterators-and-generators)). A callable string or array whose target
is not known may do anything, so a pure function may not call one.

Destroying an object calls its destructor, where the object dies: a pure function may not hold, in
a local variable, an object it created with `new` whose `__destruct` has effects, unless the object
leaves the function (returned, stored, passed on, captured), nor `unset()` a variable holding such
an object, nor discard one (`new Guard();`).

Parameter default values are evaluated like in Hack, with no capabilities at all, or with the
globals when the function-like may write them (`write-globals`), whatever else the function-like
may do; only function-likes without a `@psalm-capabilities` annotation may use anything in a
default value, so `new` in the defaults of unannotated code stays free.

With `rememberPropertyAssignmentsAfterCall="false"`, what is known about the properties and static
properties of the objects in scope survives a call to a function-like that cannot write properties
(for the former) or globals (for the latter): a call of a function-like, constructor or static
method with no more than `read-props|read-globals` keeps every refinement.

Creating a closure is never an effect: a pure function may build and return an impure closure. The
closure's capabilities are carried by its type (see [callable types](type_syntax/callable_types.md#pure-callables))
and are required where it is called or passed:

```php
<?php
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

A closure that captures a variable by reference (`use (&$x)`) shares it with the enclosing
scope: reading it needs `read-props` and writing it `write-this-props`, as if it were a property
of the closure, so such a closure is not pure. The enclosing scope may still call it freely,
since the variable is its own, and only needs a capability when the variable is shared further:
`write-refs` for a by-reference parameter, `write-globals` for a global.

```php
<?php
/**
 * @psalm-pure
 * @param list<int> $xs
 */
function sum(array $xs): int {
    $total = 0;
    $add = function (int $v) use (&$total): void { $total += $v; };
    foreach ($xs as $x) {
        $add($x); // fine: $total belongs to sum()
    }
    return $total;
}

/** @param Closure<pure>(int): void $f */
function each(Closure $f): void {}

/** @psalm-pure */
function leak(): void {
    $total = 0;
    each(function (int $v) use (&$total): void { $total += $v; }); // error: the closure is write-this-props
}
```

A caller normally needs every capability of the functions it calls, with two differences.

A method writes the properties of its own `$this`, which is not always the caller's. Calling a
method that needs `write-this-props` needs `write-this-props` when the receiver is the caller's
`$this`, `write-props` when it is any other object, and nothing when it is an object the caller
created itself from a class whose methods need at most `write-this-props|write-refs`, which
nobody else can see change. So a pure function may create such an object and call its mutating
methods:

```php
<?php
/** @psalm-capabilities write-this-props */
final class Counter {
    private int $n = 0;

    public function inc(): void {
        $this->n++;
    }

    public function get(): int {
        return $this->n;
    }
}

/** @psalm-pure */
function countTwice(): int {
    $c = new Counter();
    $c->inc(); // fine: $c was created here, nobody else can see it change
    $c->inc();
    return $c->get();
}

/** @psalm-pure */
function bump(Counter $c): int {
    $c->inc(); // error: $c belongs to the caller, writing it needs write-props
    return 0;
}
```

A call to a function with `write-refs` does not need `write-refs` itself. Instead, each argument
passed by reference needs what writing that argument directly would need: nothing for a local
variable, `write-refs` for one of the caller's own by-reference parameters, `write-this-props` for a
property of `$this`, `write-props` for any other property, `write-globals` for global state. The
callee still only has `write-refs`:

```php
<?php
final class Stats {
    public static int $n = 0;
}

/** @psalm-capabilities write-refs */
function inc(int &$i): void {
    $i++;
}

/** @psalm-capabilities write-refs */
function f(int &$r): void {
    $local = 0;
    inc($local);    // fine: $local belongs to f()
    inc($r);        // fine: like writing $r directly, needs write-refs
    inc(Stats::$n); // error: like writing Stats::$n directly, needs read-globals and write-globals
}
```

### `@psalm-immutable`

Used to annotate a class where every property is treated by consumers as `@psalm-readonly` and every instance method is treated as `@psalm-capabilities read-props`.

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

### `@psalm-mutable`

Used to annotate a class where at least one property is mutable: this is the default behavior, but it can be explicitly marked for clarity.

### `@psalm-purity-template`

Declares a **purity template**: a template parameter whose values are capability sets rather
than types. It is used as the purity of a closure type (`Closure<P>(int): int`,
`callable<P>(): void`) and as a class template argument (`Doer<pure>`, `Doer<P>`). Purity
templates are covariant: a `Doer<pure>` can be used where a `Doer<io>` is expected.

Together with `@psalm-purity-from-template`, it makes a function-like's purity depend on the
closures it is given, like Hack's `[ctx $f]` contexts.

The bounds of a purity template are written as a chain around its name,
`lower <= Name(default) <= upper`, where every part but the name may be omitted:

- the upper bound is the most a value of the template may require (`impure` when omitted):
  `P <= write-props|io`;
- a class purity template may also have a lower bound, the least every value requires, which the
  methods depending on the template may then use unconditionally: `write-this-props <= C`;
- and a default in parentheses, for the subclasses that do not bind it: `C(pure)`.

Several templates can be declared in one tag, separated by commas (`P <= io, Q`). In a chain with
a single bound, the side that is a capability is the bound: `io <= C` is a lower bound and `C <= io`
an upper bound. A type alias used as a lower bound needs the upper bound written out as well
(`Alias <= C <= impure`).

In Hack these are the bounds and default of a context constant, with the keywords reversed as
contexts are types: `abstract const ctx C super [write_props, io] as [write_this_props] = [write_this_props]`.

```php
<?php
/** @psalm-purity-template write-this-props <= C(write-this-props) <= write-props|io */
abstract class Doer {
    public int $runs = 0;

    /**
     * @psalm-capabilities read-props
     * @psalm-purity-from-template C
     */
    public function run(): int {
        $this->runs++; // fine: every C includes write-this-props
        return $this->runs;
    }
}

/** @extends Doer<write-globals> */
final class GlobalDoer extends Doer {} // InvalidTemplateParam: beyond the upper bound

/** @extends Doer<pure> */
final class PureDoer extends Doer {} // InvalidTemplateParam: below the lower bound

final class DefaultDoer extends Doer {} // C is write-this-props
```

The common case, a function whose purity depends on one closure parameter, needs no template
of its own: `Closure<_>(...)` (or `callable<_>(...)`) in a parameter's type declares one and makes
the function inherit its purity from that parameter, like Hack's `(function()[_]: T) $f`:

```php
<?php
/**
 * @psalm-pure
 * @param Closure<_>(int): int $callback
 */
function apply(Closure $callback): int {
    return $callback(1);
}
```

### Iterators and generators

`Traversable`, `Iterator`, `IteratorAggregate` and `Generator` carry a purity template after
their key and value templates, `TPurity`, which says what iterating over the object may do:
`Iterator<int, string, pure>` can be iterated by a pure function, `Generator<int, int, mixed, void, io>`
prints when consumed. It defaults to `impure`, so `Iterator<int, string>` still means an iterator
about which nothing is known. Iterating a value known only by one of these types costs its
`TPurity` and nothing else: a pure function may consume any `Iterator<int, string, pure>` or
`Generator<int, int, mixed, mixed, pure>`, including one it was given (where a generator is paused
is internal engine state). Iterating a value of an iterator class calls that class's own methods,
so it costs what they do like any other method call: writing the iterator's properties costs
nothing when it was just created, `write-this-props` when it is `$this` and `write-props`
otherwise.

A generator function-like with a purity annotation binds the `TPurity` of the `Generator`
(or `Iterator`, `Traversable`) it returns to its own capabilities, and to its purity templates,
which every call resolves: consuming the generator costs what running its body costs.

```php
<?php
/**
 * @psalm-pure
 * @param Closure<_>(): int $f
 * @return Generator<int, int>
 */
function map(Closure $f): Generator { yield $f(); return 0; }

/** @psalm-pure */
function sum(): int {
    $total = 0;
    foreach (map(fn(): int => 1) as $x) { // Generator<int, int, mixed, mixed, pure>
        $total += $x;
    }
    return $total;
}

/** @psalm-pure */
function print(): int {
    $g = map(function (): int { echo "x"; return 1; }); // Generator<int, int, mixed, mixed, io>
    foreach ($g as $x) {} // error: iterating over the generator requires io
    return 0;
}
```

A class implementing `Iterator` or `IteratorAggregate` may bind `TPurity` in its `@implements`
(`@implements Iterator<int, string, pure>`), in which case its iteration methods (or its
`getIterator()` and what that returns) must fit the binding. A class that does not bind it gets
it from those methods, as whoever iterates it sees them: a method writing the iterator's own
properties makes it `write-props`. So `MyIterator` is accepted where `Iterator<int, string, pure>`
is expected exactly when its iteration methods are pure.

### `@psalm-purity-from-template`

Used to make a function-like's purity depend on one or more templates: each call needs the
function-like's own capabilities plus those of the closures the templates are bound to at that
call. Inside the body, calling a closure whose purity is one of these templates is not an effect
of its own; the responsibility is deferred to each caller. The templates can be purity templates
of the function-like or of its class, or type templates bound to a closure or callable type.

```php
<?php

/**
 * @psalm-pure
 * @psalm-purity-template P
 * @param Closure<P>(int): int $callback
 * @psalm-purity-from-template P
 */
function apply(Closure $callback): int {
    return $callback(1);
}

/** @psalm-pure */
function usePure(): int {
    return apply(fn(int $x): int => $x + 1); // ok: the closure is pure
}

/** @psalm-capabilities io */
function useIo(): int {
    return apply(function (int $x): int { echo $x; return $x; }); // ok: the call needs io
}

/** @psalm-pure */
function bad(): int {
    return apply(function (int $x): int { echo $x; return $x; }); // ImpureFunctionCall
}
```

The same works for static methods and constructors. A class-level purity template describes
classes whose purity is decided by their subclasses, or by what they are constructed with:

```php
<?php

/** @psalm-purity-template C */
abstract class Task {
    /**
     * @psalm-capabilities read-props
     * @psalm-purity-from-template C
     */
    abstract public function run(): int;
}

/** @extends Task<pure> */
final class Sum extends Task {
    /** @psalm-pure */
    public function run(): int { return 1; }
}

/** @extends Task<io> */
final class Printer extends Task {
    /** @psalm-capabilities io */
    public function run(): int { echo "x"; return 1; }
}

/** @psalm-pure */
function runSum(Sum $t): int { return $t->run(); } // ok

/** @psalm-pure */
function runAny(Task $t): int { return $t->run(); } // ImpureMethodCall: an unbound C is impure

/**
 * @psalm-pure
 * @psalm-purity-template P
 * @param Task<P> $t
 * @psalm-purity-from-template P
 */
function runDependent(Task $t): int { return $t->run(); } // ok: pure when given a Sum, io when given a Printer
```

A type template bound to a closure type carries the closure's purity, so this also works:

```php
<?php

/**
 * @template T of callable(): void
 */
class Deferred {
    /** @var T */
    private $callback;

    /**
     * @param T $callback
     * @psalm-capabilities write-this-props
     */
    public function __construct($callback) {
        $this->callback = $callback;
    }

    /**
     * @psalm-capabilities read-props
     * @psalm-purity-from-template T
     */
    public function run(): void {
        ($this->callback)();
    }
}

/**
 * @psalm-capabilities read-props
 * @param Deferred<pure-Closure(): void> $deferred
 */
function runPure(Deferred $deferred): void {
    $deferred->run(); // OK: T is a pure closure
}
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

When an [`Unused*` issue](../running_psalm/issues.md) is reported, there are only
two correct fix paths:

 * The symbol is genuinely **public surface** — consumed from outside the
   analysed codebase, e.g. a library entry point, or a class instantiated only
   by a framework, container or plugin loader. Mark it with `@api`/`@psalm-api`.
   Use this *only* for real public surface.
 * Otherwise the symbol is **dead code** — remove it, along with anything that
   was only reachable through it.

For a reported method or property, `@api` may be placed either on that member or
on the whole class, but marking the **whole class** is almost always the right
choice: an externally-consumed member belongs to an externally-consumed class,
and one class-level annotation then covers its entire public API. Conversely, if
a member is reported as unused inside a class that is *not* marked `@api`, it is
almost always genuinely dead code — remove it rather than annotating the lone
member. Annotating an individual member is only correct in the uncommon case
where the class is not public API but that one member genuinely is.

A class that implements an interface (or extends a class) defined outside the
project is treated as public surface automatically, since it can be instantiated
and invoked through that external type: its overriding methods are considered
used without an explicit `@api`.

Do not reach for `@psalm-suppress UnusedClass` (or another `Unused*`
suppression) in place of `@api`: as noted above, a suppression only silences the
report, it does not mark the symbol as used, so symbols it references may still
be reported as unused.

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

Psalm supports PHPDoc’s [type syntax](https://docs.phpdoc.org/guide/guides/types.html), and also the [proposed PHPDoc PSR type syntax](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types).

A detailed write-up is found in [Typing in Psalm](typing_in_psalm.md)
