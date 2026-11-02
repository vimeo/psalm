# Annotations cheat sheet

Full reference: `docs/annotating_code/supported_annotations.md` and `docs/annotating_code/type_syntax/`.
Psalm reads `@param`, `@return` and `@var` with its own type syntax. The `@psalm-` prefix (`@psalm-param`,
`@psalm-return`) is only needed when other tools such as an IDE choke on a type; Psalm then takes the
`@psalm-` version.

## Arrays

| Type | Means |
|---|---|
| `list<T>` | keys `0..n-1` without gaps. `array_filter()` and `unset()` break this, `array_values()` restores it |
| `non-empty-list<T>` | a list with at least one element; `$list[0]` is safe |
| `array<K, V>` | any keys of type `K` |
| `array{id: int, name?: string}` | shape: `id` always set, `name` optional; other keys are not allowed |
| `array{id: int, ...<string, mixed>}` | shape that allows more keys |
| `list{int, string}` | tuple |

A function that builds an array with fixed keys should return a shape, so that callers get typed values:

```php
/** @return array{sku: string, qty: int} */
function parseLine(string $line): array { … }
```

Name a shape used in several places with `@psalm-type` and reuse it with `@psalm-import-type`:

```php
/** @psalm-type OrderRow = array{sku: string, qty: int} */
final class OrderImporter {}

/** @psalm-import-type OrderRow from OrderImporter */
final class OrderPrinter {
    /** @param OrderRow $row */
    public function print(array $row): void {}
}
```

## Generics

```php
/**
 * @template T
 */
final class Collection
{
    /** @var list<T> */
    private array $items = [];

    /** @param T $item */
    public function add(mixed $item): void { $this->items[] = $item; }

    /** @return T|null */
    public function first(): mixed { return $this->items[0] ?? null; }
}

/** @return Collection<string> */
function names(): Collection { … }
```

- `new Collection()` without a constructor argument of type `T` is `Collection<mixed>`. Fix `T` on the
  assignment: `/** @var Collection<string> $names */ $names = new Collection();`.
- Extending: `@extends Collection<User>`, `@implements IteratorAggregate<int, User>`, `@use SomeTrait<User>`.
- A class name as an argument: `@param class-string<T> $class` with `@return T`.
- `@template-covariant T` when `Collection<Child>` must be accepted where `Collection<Parent>` is expected;
  `T` may then only appear in return positions.
- Details: `docs/annotating_code/templated_annotations.md`.

## Assertions

A function that throws unless its argument has a type tells Psalm so with `@psalm-assert`:

```php
/** @psalm-assert User $value */
function assertUser(mixed $value): void
{
    if (!$value instanceof User) {
        throw new InvalidArgumentException('Expected a User');
    }
}

/** @psalm-assert-if-true non-empty-string $value */
function isFilled(?string $value): bool { return $value !== null && $value !== ''; }
```

`@psalm-assert-if-false` works the same way for a `false` result. Psalm infers simple assertions on its own
for functions that can't be overridden. See `docs/annotating_code/adding_assertions.md`.

## Conditional return types

```php
/** @return ($asString is true ? string : int) */
function id(bool $asString): string|int { … }

/**
 * @template T of object
 * @param class-string<T> $class
 * @return T
 */
function make(string $class): object { … }
```

## Scalar and utility types

`non-empty-string`, `numeric-string`, `class-string`, `literal-string`, `lowercase-string`,
`positive-int`, `non-negative-int`, `int<1, 100>`, `int<0, max>`, `'a'|'b'` literal unions,
`key-of<T>`, `value-of<T>`, `Foo::BAR_*` constants. See `docs/annotating_code/type_syntax/`.

## Properties and purity

- `@readonly` / `@psalm-readonly` on a property: assignable only in the constructor. Prefer the native
  `readonly`.
- `@psalm-immutable` on a class: all properties read-only, methods may only read properties.
- `@psalm-pure` on a function: the result depends only on its arguments.
- `@psalm-capabilities read-props|io` lists the side effects a function may have (replaces
  `@psalm-mutation-free` and `@psalm-external-mutation-free`, which still work). See
  `docs/annotating_code/purity_model.md`.

## Dead-code annotations

- `@psalm-api` (or `@api`) on a class: used from outside the analysed code; its public members are not
  reported as unused. Put it on the class rather than on single members.
- `@psalm-internal Vendor\Package` on a symbol: only that namespace may use it; using it elsewhere is
  `InternalClass` / `InternalMethod`.

## Variables

- `/** @var Foo $x */` before an assignment overrides the inferred type without a check. It is correct for
  a value whose type a library guarantees but its signature doesn't say, and for fixing a template on
  `new`. For outside data, check the type at runtime instead.
- `@param-out T $ref` for by-reference parameters.
