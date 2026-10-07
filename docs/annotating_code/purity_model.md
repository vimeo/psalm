# The Purity Model

Psalm describes what a function, method or closure may do as a set of **capabilities**:
permissions to perform particular side effects, such as writing a property or printing. The
model comes from Hack's [contexts and capabilities](https://docs.hhvm.com/hack/contexts-and-capabilities/introduction).
It replaces the three fixed levels of Psalm 6 (`@psalm-pure`, `@psalm-mutation-free` and
`@psalm-external-mutation-free`), which are now three points in a larger space.

This page explains how the model works. The annotations are listed in
[Supported Annotations](supported_annotations.md#purity-and-capabilities). The examples only
mark the issues they are about: Psalm also suggests annotations for the unannotated code in them
(see [Inferring annotations](#inferring-annotations)).

## Capabilities

| Capability         | Allows                                                                          |
|--------------------|---------------------------------------------------------------------------------|
| `read-props`       | reading properties of mutable objects, including `$this` (immutable objects never need it) |
| `write-this-props` | writing or unsetting properties of `$this`                                      |
| `write-props`      | writing or unsetting properties of any other object                             |
| `read-globals`     | reading static properties and superglobals, and binding `global` variables      |
| `write-globals`    | writing them, including through a bound `global` variable, and using `static` variables |
| `write-refs`       | writing through by-reference parameters and other references into another scope |
| `io`               | `echo`, `print`, `exit` with a message, and builtins with side effects (`time`, `random_int`, `file_put_contents`, …) |

Builtins touching process-wide state (`mt_rand`, `ini_set`, `spl_autoload_register`, …) need
`write-globals` instead of `io`.

Two names stand for the extremes: `pure` is the empty set, a
[pure function](https://en.wikipedia.org/wiki/Pure_function) whose result depends only on its
arguments, and `impure` is every capability. A set is written with `|` or commas:
`read-props|write-this-props`.

A function-like may only do something, or call something, when its own set contains every
capability that needs. Code without an annotation is `impure`: it may do anything, and nothing
inside it is checked.

Each name means that one capability only. Writing does not include reading, so `$this->n++`
needs `read-props|write-this-props`, and code that writes both `$this` and other objects needs
`write-this-props|write-props`. `write-props` does not include `write-this-props`, and
`write-globals` does not include `read-globals`. A plain assignment only needs the write
capability: `$this->n = 0` needs `write-this-props`, `Counter::$count = 0` needs `write-globals`.
Using `$this` at all needs `read-props` or `write-this-props`, so a setter returning `$this` may be
`write-this-props`.

```php
<?php
final class Counter {
    public static int $count = 0;
    private int $n = 0;

    /** @psalm-capabilities read-props|write-this-props */
    public function next(): int {
        return ++$this->n;
    }

    /** @psalm-capabilities write-this-props */
    public function nextWrong(): int {
        return ++$this->n; // ImpurePropertyFetch: The context is write-this-props but accessing a property on a mutable object requires read-props
    }
}

/** @psalm-capabilities read-globals */
function total(): int {
    return Counter::$count;
}

/** @psalm-capabilities read-globals */
function resetTotal(): int {
    Counter::$count = 0; // ImpureStaticProperty: The context is read-globals but writing a static property requires write-globals
    return 0;
}
```

### Global state

Values reached from global state stay bound to it. An object read from a static property, a
superglobal or a `global` variable, returned by a function that may read globals, or fetched from
such an object, can only have its properties written, its mutating methods called, or be passed
to a function that may write properties, by code that has `write-globals`. This makes
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
    $config = config();
    if ($config !== null) {
        $config->env = "dev"; // ImpurePropertyAssignment: The context is write-props|read-globals but property assignment to Config::$env on an object reached from global state requires write-props|write-globals
    }
}
```

## Annotations

Every purity annotation is a name for a capability set:

| Annotation                      | Used on                          | Capabilities                                       |
|---------------------------------|----------------------------------|----------------------------------------------------|
| `@psalm-pure`                   | function, method, class          | `pure`; on a class, properties can't be used at all |
| `@psalm-capabilities <set>`     | function, method, closure, class | `<set>`                                            |
| `@psalm-impure`                 | function, method                 | `impure`, written out                              |
| `@psalm-immutable`              | class                            | `read-props` for every method, and every property is readonly |
| `@psalm-mutable`                | class                            | `impure`, written out                              |
| `@psalm-mutation-free`          | function, method, class          | `read-props` (legacy)                              |
| `@psalm-external-mutation-free` | function, method, class          | `read-props\|write-this-props\|write-refs` (legacy) |
| none                            |                                  | `impure`                                           |

`@pure`, `@phpstan-pure`, `#[Psalm\Pure]` and `#[JetBrains\PhpStorm\Pure]` mean the same as
`@psalm-pure`; `#[Psalm\Immutable]` and `#[JetBrains\PhpStorm\Immutable]` the same as
`@psalm-immutable`; `#[Psalm\ExternalMutationFree]` the same as `@psalm-external-mutation-free`.

### Capability aliases

A capability set can be named once with a type alias and used in `@psalm-capabilities`, in
closure types and as a purity argument, like Hack's context constants. Aliases of other classes
are imported with `@psalm-import-type`:

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
     * @param Closure[Storage](): void $after
     */
    public function run(Repo $repo, Closure $after): void {
        $repo->save();
        $after();
    }
}
```

### Closures

Closures don't need an annotation. Psalm infers their capabilities from their bodies and keeps
them in their type, for example `Closure[io](int): void` (see
[callable types](type_syntax/callable_types.md#pure-callables)). Creating a closure is not an
effect: a pure function may build and return an impure closure. Calling it, or passing it to a
parameter that expects fewer capabilities, is checked.

A closure that captures a variable by reference (`use (&$x)`) shares it with the enclosing
scope: reading it needs `read-props` and writing it `write-this-props`, as if it were a property
of the closure, so such a closure is not pure. The enclosing scope may still call it freely,
since the variable is its own, and only needs a capability when the variable is shared further:
`write-refs` for a by-reference parameter, `write-globals` for a global.

```php
<?php
/**
 * @psalm-pure
 * @param list<int> $numbers
 */
function sum(array $numbers): int {
    $total = 0;
    $add = function (int $value) use (&$total): void { $total += $value; };
    foreach ($numbers as $number) {
        $add($number); // fine: $total belongs to sum()
    }
    return $total;
}

/**
 * @psalm-pure
 * @param Closure[pure](int): void $callback
 */
function callWithOne(Closure $callback): int {
    $callback(1);
    return 1;
}

/** @psalm-pure */
function leak(): int {
    $total = 0;
    return callWithOne(function (int $value) use (&$total): void { $total += $value; }); // ArgumentTypeCoercion: Argument 1 of callWithOne expects Closure[pure](int):void, but parent type Closure[read-props|write-this-props](int):void provided
}
```

## Class-level contracts

`@psalm-capabilities` on a class or interface is a contract for the class and for everything that
extends or implements it. Every method of the class gets those capabilities, and may declare fewer
but not more:

```php
<?php
/** @psalm-capabilities io */
final class Logger {
    public static int $level = 0;

    public function log(string $message): void {
        echo $message; // fine: the class allows io
    }

    /** @psalm-capabilities io|write-globals */
    public function setLevel(int $level): void { // ImpureFunctionCall: setLevel is marked @psalm-capabilities write-globals|io but its containing class allows fewer capabilities, @psalm-capabilities io
        self::$level = $level;
    }
}
```

A class extending or implementing one with a contract must have a contract that fits inside it;
otherwise Psalm reports [ImmutableDependency](../running_psalm/issues/ImmutableDependency.md).
Abstract methods, and the methods of interfaces, have no body to infer anything from, so each of
them needs its own annotation, even inside a class with a contract
([MissingAbstractPureAnnotation](../running_psalm/issues/MissingAbstractPureAnnotation.md)); use
`@psalm-impure` for one that may do anything.

## How calls are charged

A caller normally needs every capability of the function-likes it calls. Two kinds of calls are
charged more precisely.

### Method calls

A method that needs `write-this-props` writes its own `$this`, which may or may not be the
caller's. The call is charged according to the receiver:

- the caller's own `$this`: `write-this-props`;
- an object the caller created with `new`, from a class whose contract needs at most
  `read-props|write-this-props|write-refs`: nothing, as nobody else can see it change;
- any other object, including a parameter, a property of `$this`, or an object returned by
  another call: `write-props`.

```php
<?php
/** @psalm-capabilities read-props|write-this-props */
final class Counter {
    private int $n = 0;

    public function inc(): void {
        $this->n++;
    }

    public function incTwice(): void {
        $this->inc(); // fine: costs write-this-props
        $this->inc();
    }

    public function get(): int {
        return $this->n;
    }
}

/** @psalm-pure */
function countTwice(): int {
    $counter = new Counter();
    $counter->inc(); // fine: $counter was created here
    $counter->inc();
    return $counter->get();
}

/** @psalm-capabilities read-props */
function bump(Counter $counter): int {
    $counter->inc(); // ImpureMethodCall: The context is read-props but method Counter::inc requires write-props
    return $counter->get();
}
```

The waiver for new objects needs the class-level contract. If `Counter` had no
`@psalm-capabilities` on the class, `new Counter()` followed by `->inc()` would cost
`write-props`, because a subclass could do more.

The `write-this-props` that a class purity template of the receiver is bound to
(`Doer[write-this-props]`, see [Purity templates](#purity-templates)) is charged the same way,
except that it is never waived for a new object. A receiver reached from
[global state](#global-state) also costs `write-globals`.

### By-reference arguments

A call to a function-like with `write-refs` does not cost `write-refs` itself. Each argument
passed by reference costs what writing it directly would cost: nothing for a local variable,
`write-refs` for one of the caller's own by-reference parameters, `write-this-props` for a property
of `$this`, `write-props` for another object's property, and `write-globals` for global state.
The callee itself still only has `write-refs`. This includes builtins such as `sort()`, so a pure
function may sort a local array.

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
function update(int &$ref): void {
    $local = 0;
    inc($local);    // fine: $local belongs to update()
    inc($ref);      // fine: like writing $ref directly, costs write-refs
    // both reported on the next line:
    // ImpureStaticProperty: The context is write-refs but reading a static property requires read-globals
    // ImpureFunctionCall: The context is write-refs but function call on inc requires write-globals
    inc(Stats::$n);
}
```

### Implicit calls

Everything a function-like does is checked, including what happens implicitly: `clone` calls
`__clone`, string interpolation and casts call `__toString`, `$object()` calls `__invoke`, array
access on objects calls the `ArrayAccess` methods, `throw new` and `new $className` call the
constructor (an unknown class may do anything), and `foreach` over an object calls its iteration
methods (see [Iterators and generators](#iterators-and-generators)). A callable string or array
whose target is not known may do anything, so a pure function may not call one.

### Destructors

Destroying an object calls its destructor, where the object dies. So a pure function may not
hold, in a local variable, an object it created with `new` whose `__destruct` has effects, unless
the object leaves the function (returned, stored, passed on, captured). Nor may it `unset()` a
variable holding such an object, or discard one (`new Guard();`).

### Parameter default values

Parameter default values are evaluated like in Hack: with no capabilities at all, or with
`read-globals|write-globals` when the function-like has `write-globals`, whatever else it may do.
Only function-likes without a purity annotation may use anything in a default value, so `new` in
the defaults of unannotated code stays free.

## Purity templates

Sometimes what a function does depends on what it is given: `array_map()` is pure when its
callback is pure. A **purity template** is a template parameter whose value is a capability set
instead of a type. It is declared with
[`@psalm-purity-template`](supported_annotations.md#psalm-purity-template).

### Binding a purity template

Purity arguments are written in square brackets, before any type arguments, as in Hack:

- `Closure[P](int): int`, `callable[pure](): void`: the purity of a callable;
- `Handler[pure]`, `Box[io]<int>`: the purity argument of a class with a purity template;
- `@extends Handler[pure]`, `@implements Iterator[pure]<int, string>`: a subclass binding it.

The purity templates of a class come after its type templates, whatever the order of the
`@template` and `@psalm-purity-template` tags. A class with `@template T` and
`@psalm-purity-template P` is used as `Box[pure]<int>`, as `Box<int>` for its default purity, or
as `Box[pure]` for its default type arguments. Several purity arguments are separated by commas
(`Pair[pure, io]<int>`), and each is a capability set (`write-props|io`) or a purity template.
An empty set is written `pure`; `Handler[]` is an array of `Handler`.

Purity templates are covariant: a `Handler[pure]` can be used where a `Handler[io]` is expected.

The bounds of type templates may use purity templates, which are then inferred with them: a
class with `@template TIterator as Traversable[P]<K, V>` and `@psalm-purity-template P`,
constructed with a `Generator[pure]<int, string, mixed, void>`, is a
`Foo[pure]<int, string, Generator[pure]<int, string, mixed, void>>`.

### `@psalm-purity-from-template`

[`@psalm-purity-from-template P`](supported_annotations.md#psalm-purity-from-template) says that
each call needs the function-like's own capabilities plus whatever `P` is bound to at that call.
Inside the body, calling a closure whose purity is `P` is not an effect: each caller pays for it,
like with Hack's `[ctx $f]` contexts. It works for functions, methods, static methods and constructors. `P` may be a purity template of
the function-like or of its class, or a type template bound to a closure or callable type.

With a class-level template, subclasses decide the purity:

```php
<?php
/** @psalm-purity-template P */
abstract class Handler {
    /**
     * @psalm-pure
     * @psalm-purity-from-template P
     */
    abstract public function handle(string $input): string;
}

/** @extends Handler[pure] */
final class Upper extends Handler {
    /** @psalm-pure */
    public function handle(string $input): string {
        return strtoupper($input);
    }
}

/** @extends Handler[io] */
final class Printer extends Handler {
    /** @psalm-capabilities io */
    public function handle(string $input): string {
        echo $input;
        return $input;
    }
}

/** @psalm-pure */
function viaUpper(Upper $handler): string {
    return $handler->handle('a'); // fine: Upper binds P to pure
}

/**
 * @psalm-pure
 * @param Handler[pure] $handler
 */
function viaPureHandler(Handler $handler): string {
    return $handler->handle('a'); // fine
}

/**
 * @psalm-pure
 * @psalm-purity-template Q
 * @param Handler[Q] $handler
 * @psalm-purity-from-template Q
 */
function viaSomeHandler(Handler $handler): string {
    return $handler->handle('a'); // fine: pure when given an Upper, io when given a Printer
}

/** @psalm-pure */
function viaAnyHandler(Handler $handler): string {
    return $handler->handle('a'); // ImpureMethodCall: The context is pure but method Handler::handle requires impure
}
```

With a function-level template, the closures passed in decide the purity:

```php
<?php
/**
 * @psalm-pure
 * @psalm-purity-template P
 * @param Closure[P](int): int $callback
 * @psalm-purity-from-template P
 */
function apply(Closure $callback): int {
    return $callback(1);
}

/** @psalm-pure */
function usePure(): int {
    return apply(fn(int $x): int => $x + 1); // fine: the closure is pure
}

/** @psalm-capabilities io */
function useIo(): int {
    return apply(function (int $x): int { echo $x; return $x; }); // fine: the call costs io
}

/** @psalm-pure */
function usePrinting(): int {
    return apply(function (int $x): int { echo $x; return $x; }); // ImpureFunctionCall: The context is pure but function call on apply requires io
}
```

A type template bound to a closure type carries the closure's purity, so it works with
`@psalm-purity-from-template` too:

```php
<?php
/**
 * @template T of callable(): void
 */
final class Deferred {
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
    $deferred->run(); // fine: T is a pure closure
}
```

### The `_` shorthand

The common case, a function whose purity follows one closure parameter, needs no named template:
`Closure[_](...)` or `callable[_](...)` in a `@param` type declares one, and the function takes
its purity from that parameter, like Hack's `(function()[_]: T) $f`. This is the same `apply()` as
above:

```php
<?php
/**
 * @psalm-pure
 * @param Closure[_](int): int $callback
 */
function apply(Closure $callback): int {
    return $callback(1);
}
```

`_` may appear anywhere in a parameter's type (`list<Closure[_](int): int>`,
`Traversable[_]<int, string>`). Every `_` in one parameter's type stands for the same template,
which has no name of its own, so it never clashes with a template declared in the docblock. `_`
can only be used in `@param` types: in `@return`, `@param-out`, `@psalm-assert`, `@var`,
`@property` or `@template` bounds it has no parameter to stand for.

On a promoted constructor parameter, `_` makes `new` take the purity of the closure passed for
it, as for any other parameter. The property, though, outlives that call, so its type uses the
template's bound: here `$f` is an `impure-Closure(): int`, and calling it needs every capability,
whatever closure was passed.

```php
<?php
final class Holder {
    /**
     * @psalm-pure
     * @param Closure[_](): int $f
     */
    public function __construct(public Closure $f) {}
}
```

To keep the closure's purity in the property, use a class purity template instead
(`@psalm-purity-template P` on the class, `@param Closure[P](): int $f` on the constructor): then
`new Holder(fn(): int => 1)` is a `Holder[pure]`, whose `$f` is a pure closure.

The parameters of an `@method` may use `_` too. A call to such a method needs what `__call` (or
`__callStatic`) needs, plus the capabilities of the closures passed for its `_` parameters:

```php
<?php
/**
 * @method int run(Closure[_](): int $callback)
 */
final class Runner {
    /** @psalm-pure */
    public function __call(string $name, array $args): int { return 1; }
}

/** @psalm-pure */
function usePure(Runner $runner): int {
    return $runner->run(fn(): int => 1); // fine: the closure is pure
}
```

### Defaults and bounds

The bounds of a purity template are written as a chain, `lower <= Name(default) <= upper`, where
every part but the name is optional:

- the upper bound is the most a value may need (`impure` when left out): `P <= write-props|io`;
- on a class, the lower bound is what every value needs, which the methods depending on the
  template may then use unconditionally: `write-this-props <= C`;
- the default, in parentheses, applies to subclasses that don't bind the template: `C(pure)`.

Several templates can be declared in one tag, separated by commas (`P <= io, Q`). In a chain with
a single bound, the side that is a capability is the bound: `io <= C` is a lower bound and
`C <= io` an upper bound. A type alias used as a lower bound needs the upper bound written out
as well (`Alias <= C <= impure`). In Hack these are the bounds and default of a context
constant, with the keywords reversed as contexts are types:
`abstract const ctx C super [write_props, io] as [write_this_props] = [write_this_props]`.

A binding must include the lower bound itself: `Doer[io]` is rejected, write `Doer[write-this-props|io]`.

```php
<?php
/** @psalm-purity-template write-this-props <= C(write-this-props) <= write-this-props|write-props|io */
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

/** @extends Doer[write-this-props|io] */
final class LoudDoer extends Doer {} // fine: between the bounds

/** @extends Doer[write-globals] */
final class GlobalDoer extends Doer {} // InvalidTemplateParam: Extended template param C expects type write-this-props|write-props|io, type write-globals given

/** @extends Doer[pure] */
final class PureDoer extends Doer {} // InvalidTemplateParam: Extended template param C must include at least write-this-props, pure given

final class DefaultDoer extends Doer {} // C is write-this-props
```

### Constructors

A constructor may take its purity from a class purity template. `new Sub()` then costs what `Sub`
binds the template to, or its default or upper bound when `Sub` doesn't bind it, and
`new static()` costs the template itself, as it may instantiate any class extending the class.

`new` can also bind a class purity template from what the constructor is given. A parameter
typed `Closure[P]` binds it from the purity of a closure. To bind it from the *kind* of argument
instead, give the constructor a `@return`: the type of the `new` expression, which is the class
itself with its template and purity arguments. Like any return type, it may be conditional.
`ArrayObject` is the typical case: its mutators only write the object itself when it stores an
array, but also write the object it wraps when given one. A class like it can say so:

```php
<?php
/**
 * @template TValue
 * @psalm-purity-template TStorage(write-props) <= write-props
 * @psalm-capabilities read-props|write-this-props
 */
final class Store {
    private int $writes = 0;

    /**
     * @param array<TValue>|object $storage
     * @return ($storage is array ? Store[pure]<TValue> : Store[write-props]<TValue>)
     * @psalm-pure
     */
    public function __construct(array|object $storage = []) {}

    /**
     * @param TValue $value
     * @psalm-purity-from-template TStorage
     */
    public function add($value): void {
        $this->writes++;
    }
}

/** @psalm-pure */
function fromArray(): int {
    $store = new Store([1]); // Store[pure]<int>
    $store->add(2); // fine: TStorage is pure, and $store was created here
    return 1;
}

/** @psalm-pure */
function fromObject(stdClass $object): int {
    $store = new Store($object); // Store[write-props]<mixed>
    $store->add(1); // ImpureMethodCall: The context is pure but method Store::add requires write-props
    return 1;
}
```

An argument that may be either an array or an object gives the union of both types, so calling
`add()` on the result costs `write-props`. A `@return` without a condition binds type templates
the same way: with `@return Box<int>` on its constructor, `new Box()` is a `Box<int>`.

A condition can also be given to each purity argument, inside the brackets, and a branch may be a
purity template of the constructor, bound from the argument. With
`@param array<K, V>|Container[PP]<K, V> $param` and `@return self[$param is array ? pure : PP]<K, V>`,
`new Wrapper($container)` takes the purity argument of `$container`, `io` for a
`Container[io]<string, int>`. Such conditional purity arguments are resolved per call in the
`@return` of any function or method, also nested (`list<Box[$x is array ? pure : io]<int>>`), and in
`@psalm-self-out`.

The `@return` of a constructor may only name its own class (or `self` or `static`); anything else
is an `InvalidDocblock`. Since it names the class itself, a subclass inheriting the constructor,
and `new static()` in a class that isn't final, get the type `new` would give without it.

### Iterators and generators

`Traversable`, `Iterator`, `IteratorAggregate` and `Generator` have a purity template besides
their key and value templates, `TPurity`: what iterating over them may do.
`Iterator[pure]<int, string>` can be iterated by a pure function, and
`Generator[io]<int, int, mixed, void>` prints when consumed. It defaults to `impure`, so
`Iterator<int, string>` still means an iterator about which nothing is known.

Iterating over a value known only by one of these types costs its `TPurity` and nothing else: a
pure function may consume any `Iterator[pure]<int, string>` or
`Generator[pure]<int, int, mixed, mixed>`, including one it was given (where a generator is
paused is internal engine state). Iterating over an object of an iterator class calls that
class's own methods (`rewind`, `valid`, `current`, `key`, `next`, or `getIterator()` and then the
methods of the iterator it returns, which counts as new), so it costs what they need like any
other [method call](#method-calls).

`iterable` has a purity too: `iterable[pure]<int, string>` accepts arrays (iterating over an array
does nothing) and `Traversable[pure]<int, string>`, and a pure function may iterate over it. A
plain `iterable<int, string>` is `iterable[impure]<int, string>`, since it may be any
`Traversable`. The purity of an `iterable` parameter may be a purity template
(`iterable[P]<int, string>`), bound by each call to what iterating over the argument does.

A generator function-like with a purity annotation binds the `TPurity` of the `Generator` (or
`Iterator`, `Traversable`) it returns, or the purity of the `iterable` it returns, to its own
capabilities and purity templates, which every call resolves: consuming the generator costs what
running its body costs.

```php
<?php
/**
 * @psalm-pure
 * @param Closure[_](): int $f
 * @return Generator<int, int>
 */
function map(Closure $f): Generator { yield $f(); return 0; }

/** @psalm-pure */
function sum(): int {
    $total = 0;
    foreach (map(fn(): int => 1) as $x) { // fine: a Generator[pure]<int, int, mixed, mixed>
        $total += $x;
    }
    return $total;
}

/**
 * @psalm-pure
 * @param Generator[io]<int, int> $generator for example map(function (): int { echo "x"; return 1; })
 */
function printAll(Generator $generator): int {
    foreach ($generator as $x) {} // ImpureMethodCall: The context is pure but iterating over Generator[io]<int, int, mixed, mixed> requires io
    return 0;
}
```

A class implementing `Iterator` or `IteratorAggregate` may bind `TPurity` in its `@implements`
(`@implements Iterator[pure]<int, string>`); its iteration methods (or its `getIterator()` and
what that returns) must then fit the binding. A class that doesn't bind it gets it from those
methods, as whoever iterates over it sees them: a method writing the iterator's own properties
makes it `write-this-props|write-props`. So `MyIterator` is accepted where
`Iterator[pure]<int, string>` is expected exactly when its iteration methods are pure.

The SPL iterators wrapping another one (`IteratorIterator`, `FilterIterator`,
`CallbackFilterIterator`, `LimitIterator`, `NoRewindIterator`, `InfiniteIterator`) have a
`TPurity` too, bound at construction to what iterating over the inner iterator (and calling the
callback of a `CallbackFilterIterator`) does: `new CallbackFilterIterator(gen(), fn(int $v): bool => $v > 0)`
is a `CallbackFilterIterator[pure]<...>` when `gen()` returns a `Generator[pure]<...>`. A subclass of
`FilterIterator` that doesn't bind `TPurity` iterates impurely, whatever its `accept()` does.

### Builtins taking callbacks

The builtins that call the closures they are given work the same way: `array_map`, `usort`,
`preg_replace_callback`, `ArrayObject::uasort`, `Ds\Vector::map`, … need what their callbacks
need and nothing else, and `iterator_to_array` and `iterator_count` what iterating over their
argument does. `Fiber` has a purity template for what its callback does, which starting or
resuming the fiber costs. `Closure::bind()` and `bindTo()` keep the type of the closure they
rebind, and `$closure->call($newThis, ...)` is a call of the closure that writes `$newThis`
where the closure writes `$this`.

## Overrides

An override may need fewer capabilities than the method it overrides, never more. This holds for
every capability, and for interface methods as well as parent class methods:

```php
<?php
/** @psalm-immutable */
interface Shape {
    /** @psalm-capabilities read-props */
    public function area(): float;
}

/** @psalm-immutable */
final class Square implements Shape {
    public function __construct(private float $side) {}

    public function area(): float { // fine: read-props, like Shape::area
        return $this->side * $this->side;
    }
}

/** @psalm-pure */
final class Unit implements Shape {
    public function area(): float { // fine: pure needs less than read-props
        return 1.0;
    }
}

// both reported on the class name:
// ImmutableDependency: Shape is marked with @psalm-immutable, but LoggedUnit is not
// ImmutableDependency: Shape::area is read-props, but LoggedUnit::area additionally requires io
/** @psalm-capabilities io */
final class LoggedUnit implements Shape {
    public function area(): float {
        echo 'area';
        return 1.0;
    }
}
```

[ImmutableDependency](../running_psalm/issues/ImmutableDependency.md) is a level 1 issue, so it is
reported as info at the default level 2.

A subclass may bind a class purity template of its parent to one of its own
(`@psalm-purity-template P` with `@extends Filter[P]`), which each `new` then binds, possibly to
`pure`. So an override of a method with `@psalm-purity-from-template` may use the template only as
the parent does: what it needs unconditionally must fit what the parent's method needs
unconditionally, plus the template's lower bound. This is like overrides in Hack, which may only
use an abstract context constant through `this::C`.

## Property refinements after calls

By default Psalm remembers what it knows about object properties across calls. With
[`rememberPropertyAssignmentsAfterCall="false"`](../running_psalm/configuration.md#rememberpropertyassignmentsaftercall),
it forgets refinements after a call, but only those the call could invalidate:

| The callee may use                | Psalm forgets refinements of                      |
|-----------------------------------|---------------------------------------------------|
| `write-this-props` or `write-props` | properties of mutable objects                   |
| `write-globals`                   | static properties and superglobals                |
| none of these                     | nothing                                           |

This applies to function calls, method calls, static calls and `new`. With
`rememberPropertyAssignmentsAfterCall="false"`, this code reports one issue:

```php
<?php
final class Order {
    public ?int $total = null;
    public static ?string $currency = null;
}

/** @psalm-capabilities read-globals */
function currencyIsSet(): bool {
    return Order::$currency !== null;
}

/** @psalm-capabilities write-props */
function clear(Order $order): void {
    $order->total = null;
}

function kept(Order $order): int {
    if ($order->total === null) {
        return 0;
    }
    currencyIsSet();
    return $order->total; // fine: currencyIsSet() can't write properties
}

function forgotten(Order $order, Order $other): int {
    if ($order->total === null) {
        return 0;
    }
    clear($other);
    return $order->total; // NullableReturnStatement: clear() may have written $order->total
}
```

## Inferring annotations

Psalm infers the capabilities of unannotated code from its body and the callees it uses. It does
so as a fixpoint over the call graph, after the whole codebase has been analysed: a function-like
that only calls pure function-likes, annotated or inferred, is pure itself, whatever the order in
which they are declared, and mutual recursion, recursive closures and closures assigned to a
variable are handled. It reports code that could be annotated:

- [MissingPureAnnotation](../running_psalm/issues/MissingPureAnnotation.md): a function or method
  that could have `@psalm-pure` or `@psalm-capabilities`;
- [MissingImmutableAnnotation](../running_psalm/issues/MissingImmutableAnnotation.md): a class
  that could have `@psalm-pure`, `@psalm-immutable` or `@psalm-capabilities`;
- [MissingAbstractPureAnnotation](../running_psalm/issues/MissingAbstractPureAnnotation.md): an
  abstract method with no annotation;
- [MissingInterfaceImmutableAnnotation](../running_psalm/issues/MissingInterfaceImmutableAnnotation.md):
  an interface with no annotation.

The first two can be fixed automatically:

```bash
vendor/bin/psalm --alter --issues=MissingPureAnnotation,MissingImmutableAnnotation
```

Given this code:

```php
<?php
function slug(string $title): string { // MissingPureAnnotation: slug must be marked @psalm-pure to aid security analysis, run with --alter --issues=MissingPureAnnotation to fix this
    return strtolower(trim($title));
}

final class Cart { // MissingImmutableAnnotation: Cart must be marked @psalm-capabilities read-props|write-this-props|write-refs to aid security analysis, run with --alter --issues=MissingImmutableAnnotation to fix this
    private int $items = 0;

    public function addItem(): void { // MissingPureAnnotation: addItem must be marked @psalm-capabilities read-props|write-this-props|write-refs to aid security analysis, run with --alter --issues=MissingPureAnnotation to fix this
        $this->items++;
    }

    public function count(): int {
        return $this->items;
    }
}
```

`--alter` adds `@psalm-pure` to `slug()`, and `@psalm-capabilities read-props|write-this-props|write-refs`
to `Cart` and `addItem()`. Suggestions use the four named levels only: `pure`, `read-props`,
`read-props|write-this-props|write-refs` and `impure` (`@psalm-immutable` for a class at
`read-props`). Write a narrower set by hand if you want one.

Psalm doesn't suggest an annotation for an unannotated method that is overridden somewhere, nor
for the code calling it, since the annotation would restrict its overrides. Annotate it yourself
when you want a contract. It doesn't suggest one either when the function-like's
[parameter default values](#parameter-default-values) need capabilities the annotation wouldn't
give them. A function-like marked `@psalm-impure` is never reported.

These issues are always reported as errors. To turn them off, or to adopt them gradually, use
`issueHandlers`:

```xml
<issueHandlers>
    <MissingPureAnnotation errorLevel="suppress" />
    <MissingImmutableAnnotation errorLevel="suppress" />
    <MissingAbstractPureAnnotation errorLevel="suppress" />
    <MissingInterfaceImmutableAnnotation errorLevel="suppress" />
</issueHandlers>
```

## Migrating from Psalm 6

Existing annotations keep their meaning:

- `@psalm-pure` and `@psalm-immutable` are unchanged.
- `@psalm-mutation-free` means `@psalm-capabilities read-props`.
- `@psalm-external-mutation-free` means `@psalm-capabilities read-props|write-this-props|write-refs`.
  It no longer allows static properties or `static` variables: use `read-globals` or
  `write-globals` for those.

`@psalm-mutation-free` and `@psalm-external-mutation-free` are still accepted but no longer
documented or suggested. `mutation-free` and `external-mutation-free` are not capability names,
so `@psalm-capabilities mutation-free` is an `InvalidDocblock`.

What changes for your code:

- **Calls on objects of the same class are no longer free.** In Psalm 6, a method could call a
  mutating method on any object of its own class, and a `@psalm-mutation-free` method could call
  one on `$this`. Now a call on `$this` costs `write-this-props` and a call on any other object
  costs `write-props` (see [Method calls](#method-calls)).
- **Overrides are checked at every level.** Psalm 6 only reported impure overrides of
  `@psalm-external-mutation-free` methods. Now any override that needs more capabilities is
  reported, as `ImmutableDependency`.
- **`MissingImmutableAnnotation` has a new meaning.** In Psalm 6 it reported a mutable class
  inheriting from an immutable one; that is now `ImmutableDependency`. `MissingImmutableAnnotation`
  now reports a class that *could* be annotated. Review existing suppressions of it.
- **New issues are reported by default:** `MissingPureAnnotation`, `MissingImmutableAnnotation`,
  `MissingAbstractPureAnnotation` and `MissingInterfaceImmutableAnnotation`. Run `--alter` once,
  or suppress them (see [Inferring annotations](#inferring-annotations)).
- **More is checked.** Implicit calls (`__toString`, `__clone`, `__invoke`, `ArrayAccess`,
  destructors), `throw new`, `new $className` and `call_user_func` are now checked for purity.
  Builtins with side effects need specific capabilities: `io` for I/O, the clock and random
  numbers, `write-globals` for process-wide state such as `mt_rand()` or `ini_set()`.
- **Iteration has a purity.** `foreach` over an object costs the iterator's methods, or the
  `TPurity` of `Traversable`, `Iterator`, `Generator` and `iterable`, instead of always being
  impure (see [Iterators and generators](#iterators-and-generators)).
- **Callable types:** `pure-callable` and `pure-Closure` still work, and any capability set can
  now be written, as in `Closure[io](int): void`. The capability names (`pure`, `impure`,
  `read-props`, …) are now reserved type names.

The full list of changes is in
[UPGRADING.md](https://github.com/vimeo/psalm/blob/master/UPGRADING.md).
