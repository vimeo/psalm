# The Purity Model

Psalm describes what a function, method or closure may do as a set of **capabilities**:
permissions to perform particular side effects, such as writing a property or printing. The
model comes from Hack's [contexts and capabilities](https://docs.hhvm.com/hack/contexts-and-capabilities/introduction).
It replaces the three fixed levels of Psalm 6 (`@psalm-pure`, `@psalm-mutation-free` and
`@psalm-external-mutation-free`), which are now three points in a larger space.

This page explains how the model works. The reference for each annotation is in
[Supported Annotations](supported_annotations.md#purity-and-capabilities). The examples only
mark the issues they are about: Psalm also suggests annotations for the unannotated code in them
(see [Inferring annotations](#inferring-annotations)).

## Capabilities

| Capability         | Allows                                                                          |
|--------------------|---------------------------------------------------------------------------------|
| `read-props`       | reading properties of mutable objects, including `$this`                        |
| `write-this-props` | writing properties of `$this`                                                   |
| `write-props`      | writing properties of any other object                                          |
| `read-globals`     | reading static properties, superglobals and `global` variables                  |
| `write-globals`    | writing them, and using `static` variables                                      |
| `write-refs`       | writing through by-reference parameters                                         |
| `io`               | `echo`, `print`, and builtins with side effects (`time`, `random_int`, `file_put_contents`, …) |

Two names stand for the extremes: `pure` is the empty set and `impure` is every capability. A
set is written with `|` or commas: `read-props|write-this-props`.

A function-like may only do something, or call something, when its own set contains every
capability that needs. Code without an annotation is `impure`: it may do anything, and nothing
inside it is checked.

Each name means that one capability only. Writing does not include reading, so `$this->n++`
needs `read-props|write-this-props`. `write-props` does not include `write-this-props`, and
`write-globals` does not include `read-globals`.

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

Closures don't need an annotation. Psalm infers their capabilities from their bodies and keeps
them in their type, for example `Closure[io](int): void` (see
[callable types](type_syntax/callable_types.md#pure-callables)). Creating a closure is not an
effect: a pure function may build and return an impure closure. Calling it, or passing it to a
parameter that expects fewer capabilities, is checked.

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
([MissingAbstractPureAnnotation](../running_psalm/issues/MissingAbstractPureAnnotation.md)).

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

### By-reference arguments

A call to a function-like with `write-refs` does not cost `write-refs` itself. Each argument
passed by reference costs what writing it directly would cost: nothing for a local variable,
`write-refs` for one of the caller's own by-reference parameters, `write-this-props` for a property
of `$this`, `write-props` for another object's property, and `write-globals` for global state.
This includes builtins such as `sort()`, so a pure function may sort a local array.

### Everything else

Implicit calls are checked too: `clone`, string conversion (`__toString`), `$object()`,
`ArrayAccess`, `foreach` over an object, destructors, and parameter default values. Objects reached
from global state can only be mutated with `write-globals`. See
[Purity and capabilities](supported_annotations.md#purity-and-capabilities) for the details.

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

Purity templates are covariant: a `Handler[pure]` can be used where a `Handler[io]` is expected.
An empty set is written `pure`; `Handler[]` is an array of `Handler`.

### `@psalm-purity-from-template`

[`@psalm-purity-from-template P`](supported_annotations.md#psalm-purity-from-template) says that
each call needs the function-like's own capabilities plus whatever `P` is bound to at that call.
Inside the body, calling a closure whose purity is `P` is not an effect: each caller pays for it.

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

/** @psalm-pure */
function usePrinting(): int {
    return apply(function (int $x): int { echo $x; return $x; }); // ImpureFunctionCall: The context is pure but function call on apply requires io
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
`Traversable[_]<int, string>`), but only in `@param` types.

### Defaults and bounds

The bounds of a purity template are written as a chain, `lower <= Name(default) <= upper`, where
every part but the name is optional:

- the upper bound is the most a value may need (`impure` when left out);
- on a class, the lower bound is what every value needs, which the methods depending on the
  template may then use unconditionally;
- the default applies to subclasses that don't bind the template.

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

### Iterators and generators

`Traversable`, `Iterator`, `IteratorAggregate`, `Generator` and `iterable` have a purity
template, `TPurity`: what iterating over them may do. It defaults to `impure`, so a pure function
may iterate over an `iterable[pure]<int, string>` or an array, but not over a plain
`Traversable<int, string>`. A generator function with a purity annotation binds `TPurity` to its
own capabilities. See [Iterators and generators](supported_annotations.md#iterators-and-generators).

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

An override of a method with `@psalm-purity-from-template` may use the template only as the
parent does: what it needs unconditionally must fit what the parent's method needs
unconditionally, plus the template's lower bound.

Psalm doesn't suggest an annotation for an unannotated method that is overridden somewhere,
since the annotation would restrict its overrides. Annotate it yourself when you want a contract.

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

Psalm infers the capabilities of unannotated code from its body and the callees it uses, after
the whole codebase has been analysed. It reports code that could be annotated:

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

A function-like marked `@psalm-impure` is never reported.

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
