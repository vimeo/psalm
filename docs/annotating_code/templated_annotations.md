# Templating

A template is a placeholder for a type. A class or function declares it with `@template`, and Psalm replaces it with a real type wherever that class or function is used.

```php
<?php
final class User {
    public function __construct(public readonly string $name) {}
}

/** @template T */
final class Box {
    /** @param T $value */
    public function __construct(private readonly mixed $value) {}

    /** @return T */
    public function get(): mixed {
        return $this->value;
    }
}

/** @param Box<User> $box */
function name(Box $box): string {
    $user = $box->get(); // User
    return $user->name;
}

function f(User $user, string $name): void {
    $box = new Box($user); // Box<User>
    name($box); // no issue
    name(new Box($name)); // InvalidArgument
}
```

In `Box<User>`, `T` is `User`, so `get()` returns a `User`. `new Box($user)` infers `T` from the constructor argument. Without the template, `get()` returns `mixed`.

## `@template`, `@psalm-template`

`@template` on a function or method declares a template that Psalm infers from the arguments of each call. Psalm reads `@psalm-template` and `@phpstan-template` the same way.

```php
<?php
final class User {}

/**
 * @template T
 * @param list<T> $items
 * @return T|null
 */
function first(array $items): mixed {
    return $items[0] ?? null;
}

/**
 * @param list<User> $users
 * @param list<int> $ids
 */
function f(array $users, array $ids): void {
    $user = first($users); // User|null
    $id = first($ids); // int|null
}
```

`@template` on a class, interface or trait declares a template of its objects. The type arguments follow the order of the `@template` tags: in `Collection<string, User>`, `TKey` is `string` and `TValue` is `User`. Psalm reports [MissingTemplateParam](../running_psalm/issues/MissingTemplateParam.md) if a type argument is missing.

```php
<?php
final class User {
    public function __construct(public readonly string $name) {}
}

/**
 * @template TKey of array-key
 * @template TValue
 */
final class Collection {
    /** @param array<TKey, TValue> $items */
    public function __construct(private readonly array $items) {}

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function get(int|string $key): mixed {
        return $this->items[$key] ?? null;
    }

    /**
     * @template TNew
     * @param callable(TValue): TNew $callback
     * @return Collection<TKey, TNew>
     */
    public function map(callable $callback): Collection {
        return new Collection(array_map($callback, $this->items));
    }
}

/** @param Collection<string, User> $users */
function f(Collection $users): void {
    $user = $users->get('ann'); // User|null
    $names = $users->map(fn(User $user): string => $user->name); // Collection<string, string>
}

/** @param Collection<string> $users */ // MissingTemplateParam
function g(Collection $users): void {}
```

`TKey of array-key` is a [template constraint](#template-constraints). A template name is only known inside the class or function that declares it.

## @param class-string&lt;T&gt;

A `class-string<T>` parameter infers `T` from the class name that is passed.

```php
<?php
interface Logger {}
final class FileLogger implements Logger {}

interface Container {
    /**
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object;
}

function f(Container $container, string $name): void {
    $logger = $container->get(FileLogger::class); // FileLogger
    $logger = $container->get(Logger::class); // Logger
    $container->get($name); // ArgumentTypeCoercion
}
```

## Template inheritance

A class that extends a templated class gives the type arguments of its parent with `@extends` (or `@template-extends`). An argument can be a template of the child (`TypedList` below). Without the tag, Psalm reports [MissingTemplateParam](../running_psalm/issues/MissingTemplateParam.md) and each template of the parent stands for its constraint.

```php
<?php
final class User {}

/**
 * @template TKey of array-key
 * @template TValue
 */
class Collection {
    /** @param array<TKey, TValue> $items */
    public function __construct(protected array $items = []) {}

    /**
     * @param TKey $key
     * @return TValue|null
     */
    public function get(int|string $key): mixed {
        return $this->items[$key] ?? null;
    }
}

/** @extends Collection<int, User> */
final class UserCollection extends Collection {}

/**
 * @template T
 * @extends Collection<int, T>
 */
final class TypedList extends Collection {}

final class Tags extends Collection {} // MissingTemplateParam

$users = new UserCollection([new User()]);
$user = $users->get(0); // User|null
$users->get('ann'); // InvalidArgument
new UserCollection(['Ann']); // InvalidArgument

$list = new TypedList([new User()]); // TypedList<User>
$user = $list->get(0); // User|null
```

`@implements` (or `@template-implements`) does the same for an interface, and `@use` (or `@template-use`) for a trait. The `@use` docblock goes on the `use` statement.

```php
<?php
final class User {}

/** @template T of object */
interface Repository {
    /** @return T|null */
    public function find(int $id): ?object;
}

/** @template T of object */
trait StoresInMemory {
    /** @var array<int, T> */
    private array $rows = [];

    /** @return T|null */
    public function find(int $id): ?object {
        return $this->rows[$id] ?? null;
    }

    /** @param T $row */
    public function save(int $id, object $row): void {
        $this->rows[$id] = $row;
    }
}

/** @implements Repository<User> */
final class UserRepository implements Repository {
    /** @use StoresInMemory<User> */
    use StoresInMemory;
}

$users = new UserRepository();
$user = $users->find(1); // User|null
$users->save(1, new User()); // no issue
$users->save(2, new DateTime()); // InvalidArgument
```

## Template constraints

`@template T of Type` only allows `Type` and its subtypes for `T`. Inside the function or class, a `T` value can be used as a `Type`. A template without a constraint allows any type.

```php
<?php
abstract class Model {
    public int $id = 0;
}
final class User extends Model {}

/**
 * @template T of Model
 * @param T $model
 * @return T
 */
function save(Model $model): Model {
    echo $model->id; // no issue
    return $model;
}

$user = save(new User()); // User
save(new DateTime()); // InvalidArgument

/** @template T of Model */
interface Repository {}

/** @param Repository<DateTime> $dates */ // InvalidTemplateParam
function f(Repository $dates): void {}
```

A templated class written without type arguments stands for its constraints: `Repository` is `Repository<Model>`, and a `Repository<User>` is not a `Repository<Model>` (see [Template covariance](#template-covariance)). Only a template without a constraint accepts every type argument there. To accept every `Repository`, use a [star projection](#star-projection).

```php
<?php
abstract class Model {}
final class User extends Model {}

/** @template T of Model */
interface Repository {
    /** @return T|null */
    public function find(int $id): ?Model;
}

function takesBare(Repository $repository): void {
    $model = $repository->find(1); // Model|null
}

/** @param Repository<*> $repository */
function takesAny(Repository $repository): void {}

/** @param Repository<User> $users */
function f(Repository $users): void {
    takesBare($users); // InvalidArgument
    takesAny($users); // no issue
}
```

## Template defaults

`@template T = Default` gives a template a default type. A constraint comes before it: `@template T of Constraint = Default`. `@template-covariant`, `@template-contravariant`, `@psalm-template` and `@phpstan-template` take a default the same way.

Psalm uses the default where the type argument is left out:

* in a generic type that gives only the first type arguments, such as `Result<string>` in a `@param`, `@return`, `@var` or property type;
* in `@extends`, `@implements` and `@use` that leave out trailing templates;
* in a class that extends, implements or uses a templated one without that tag. A template without a default gets its constraint there, and Psalm reports [MissingTemplateParam](../running_psalm/issues/MissingTemplateParam.md);
* in `new`, for a template that the constructor arguments don't infer;
* in a function or method call, for a template that the arguments don't infer.

```php
<?php
/**
 * @template TValue = null
 * @template TError of Throwable = RuntimeException
 */
class Result {
    /**
     * @param TValue $value
     * @param TError|null $error
     */
    public function __construct(
        public readonly mixed $value = null,
        public readonly ?Throwable $error = null,
    ) {}
}

/** @extends Result<int> */
final class IntResult extends Result {}

final class EmptyResult extends Result {}

/** @param Result<string> $partial */
function f(Result $partial, IntResult $int, EmptyResult $empty, Result $bare): void {
    $partial->error; // RuntimeException|null
    $int->error; // RuntimeException|null
    $empty->value; // null
    $empty->error; // RuntimeException|null
    $bare->error; // Throwable|null
}

$result = new Result(); // Result<null, RuntimeException>
$result = new Result(null, new LogicException()); // Result<null, LogicException>
```

A class name without type arguments does not use the defaults (PHPStan uses them there). `Result` alone stands for [its constraints](#template-constraints), so `$bare->error` is `Throwable|null`.

For an invariant or covariant template, `new` does not use the default if a public method parameter or a public writable property takes the template. The class must declare that method or property itself: inherited, trait, `@method` and `@property` members don't count. Later code in the function can still fix such a template (see [Type variables](type_variables.md)). If no code does, the template stands for its constraint. `new` never leaves a [contravariant](#template-contravariance) template to later code.

```php
<?php
/** @template T = int */
final class Queue {
    /** @param T $item */
    public function push(mixed $item): void {}
}

/** @return Queue<string> */
function names(): Queue {
    $queue = new Queue();
    $queue->push('Ann');
    return $queue; // no issue
}

/** @return Queue<string> */
function ids(): Queue {
    $queue = new Queue();
    $queue->push(1); // IncompatibleTypeParameters
    return $queue;
}
```

A method template takes a default too, and the default can use the templates of the class:

```php
<?php
/** @template T */
interface Option {
    /**
     * @template TResult = T
     * @param (callable(T): TResult)|null $map
     * @return Option<TResult>
     */
    public function map(?callable $map = null): Option;
}

/** @param Option<int> $option */
function f(Option $option): void {
    $same = $option->map(); // Option<int>
    $mapped = $option->map(fn(int $i): bool => $i > 0); // Option<bool>
}
```

In a call, an argument that fixes the template wins over the default. Two cases are less obvious:

* `null` for a `T|null` parameter, passed or left to the parameter default, infers `null` if the constraint allows `null`. Otherwise Psalm uses the default.
* An argument that does not say what `T` is, such as a `Setting` without type arguments for a `Setting<T>` parameter, infers the constraint, not the default.

```php
<?php
/** @template T of int */
interface Setting {}

interface Timeouts {
    /**
     * @template T of int = 30
     * @param T|null $seconds
     * @return T
     */
    public function connect(?int $seconds = null): int;

    /**
     * @template T of int|null = 30
     * @param T|null $seconds
     * @return T
     */
    public function idle(?int $seconds = null): ?int;

    /**
     * @template T of int = 30
     * @param Setting<T> $setting
     * @return T
     */
    public function configured(Setting $setting): int;
}

/** @param Setting<5> $five */
function f(Timeouts $timeouts, Setting $bare, Setting $five): void {
    $seconds = $timeouts->connect(10); // 10
    $seconds = $timeouts->connect(); // 30
    $seconds = $timeouts->connect(null); // 30
    $seconds = $timeouts->idle(); // null
    $seconds = $timeouts->idle(null); // null
    $seconds = $timeouts->configured($five); // 5
    $seconds = $timeouts->configured($bare); // int
}
```

A default must be within the constraint of its template and can only use templates declared before it. Otherwise Psalm reports [InvalidDocblock](../running_psalm/issues/InvalidDocblock.md) on the `@template` line:

```php
<?php
/** @template T of int = string */ // InvalidDocblock
interface Counter {}
```

A default can name its own class. Psalm expands it one level: `Node<int>` below is `Node<int, Node<int>>`.

```php
<?php
/**
 * @template T
 * @template TNext of Node = Node<T>
 */
interface Node {
    /** @return TNext */
    public function next(): Node;
}

/** @param Node<int> $node */
function f(Node $node): void {
    $next = $node->next(); // Node<int, Node<int>>
    $after = $next->next(); // Node<int, Node<int>>
}
```

If the class has another default that Psalm computes, such as `@template TKey of array-key = key-of<self::MAP>`, the inner `Node<int>` does not get it: Psalm reports [MixedArgumentTypeCoercion](../running_psalm/issues/MixedArgumentTypeCoercion.md) when a `Node<int>` is passed where `Node<int, Node<int>, 'a'>` is written out.

## Template covariance

A template is invariant by default: a `Collection<Dog>` is not a `Collection<Animal>`, because code that takes a `Collection<Animal>` may add a `Cat` to it. Psalm reports the call even if the function only reads the collection.

```php
<?php
abstract class Animal {}
final class Dog extends Animal {}
final class Cat extends Animal {}

/** @template T */
final class Collection {
    /** @param list<T> $items */
    public function __construct(private array $items = []) {}

    /** @param T $item */
    public function add(mixed $item): void {
        $this->items[] = $item;
    }

    /** @return list<T> */
    public function all(): array {
        return $this->items;
    }
}

/** @param Collection<Animal> $animals */
function addCat(Collection $animals): void {
    $animals->add(new Cat());
}

/** @param Collection<Animal> $animals */
function countAnimals(Collection $animals): int {
    return count($animals->all());
}

/** @param Collection<Dog> $dogs */
function f(Collection $dogs): void {
    addCat($dogs); // InvalidArgument
    countAnimals($dogs); // InvalidArgument
}
```

`@template-covariant T` (or `@psalm-template-covariant T`) makes a `Collection<Dog>` a `Collection<Animal>`. In exchange, Psalm reports [InvalidTemplateParam](../running_psalm/issues/InvalidTemplateParam.md) for a method parameter of type `T`, as such a method could add a wrong value. A constructor parameter is fine.

```php
<?php
abstract class Animal {}
final class Dog extends Animal {}

/** @template-covariant T */
final class Collection {
    /** @param list<T> $items */
    public function __construct(private readonly array $items = []) {}

    /** @return list<T> */
    public function all(): array {
        return $this->items;
    }

    /** @param T $item */ // InvalidTemplateParam
    public function add(mixed $item): void {}
}

/** @param Collection<Animal> $animals */
function countAnimals(Collection $animals): int {
    return count($animals->all());
}

/** @param Collection<Dog> $dogs */
function f(Collection $dogs): void {
    countAnimals($dogs); // no issue
}
```

### Immutable classes

A method can take a covariant `T` if it cannot change the object: any method of a `@psalm-immutable` class, or a `@psalm-mutation-free` method. Such a method returns a new object instead. See [Immutability and beyond](https://psalm.dev/articles/immutability-and-beyond).

```php
<?php
abstract class Animal {}
final class Dog extends Animal {}

/**
 * @template-covariant T
 * @psalm-immutable
 */
final class Collection {
    /** @param list<T> $items */
    public function __construct(private array $items = []) {}

    /**
     * @param T $item
     * @return Collection<T>
     */
    public function with(mixed $item): Collection {
        return new Collection([...$this->items, $item]);
    }
}

/** @param Collection<Dog> $dogs */
function f(Collection $dogs): void {
    $more = $dogs->with(new Dog()); // Collection<Dog>
    $dogs->with('Rex'); // InvalidArgument
}
```

## Template contravariance

`@template-contravariant T` (or `@psalm-template-contravariant T`) is the opposite of `@template-covariant`. It suits a class that only takes values of type `T`: a `Sink<Animal>` takes every dog, so it is a `Sink<Dog>`.

```php
<?php
abstract class Animal {}
final class Dog extends Animal {}

/** @template-contravariant T */
interface Sink {
    /** @param T $value */
    public function put(mixed $value): void;
}

/** @param Sink<Dog> $sink */
function takesDogSink(Sink $sink): void {}

/** @param Sink<Animal> $sink */
function takesAnimalSink(Sink $sink): void {}

/**
 * @param Sink<Animal> $animal_sink
 * @param Sink<Dog> $dog_sink
 */
function f(Sink $animal_sink, Sink $dog_sink): void {
    takesDogSink($animal_sink); // no issue
    takesAnimalSink($dog_sink); // ArgumentTypeCoercion
}
```

Psalm reports [InvalidTemplateParam](../running_psalm/issues/InvalidTemplateParam.md) where the class gives out a value of type `T`: a return type, a public or protected property, or the parameter of a callback that a method takes. Psalm does not check private properties and constructor parameters. A child class can only pass a contravariant or invariant template of its own to `@extends Sink<T>`.

```php
<?php
/** @template-contravariant T */
interface Sink {
    /** @param T $value */
    public function put(mixed $value): void;
}

/** @template-contravariant T */
final class Channel {
    /** @var T */ // InvalidTemplateParam
    public mixed $last;

    /** @var T */ // no issue
    private mixed $first;

    /** @param T $first */ // no issue
    public function __construct(mixed $first) {
        $this->first = $first;
        $this->last = $first;
    }

    /** @return T */ // InvalidTemplateParam
    public function first(): mixed {
        return $this->first;
    }

    /** @param Closure(T): void $callback */ // InvalidTemplateParam
    public function each(Closure $callback): void {}

    /** @return Sink<T> */ // no issue
    public function sink(): Sink {
        throw new Exception();
    }
}

/**
 * @template-covariant T
 * @extends Sink<T>
 */
interface Source extends Sink {} // InvalidTemplateParam
```

`new` gives a contravariant template the widest type that the constructor arguments allow. An argument that takes a `T` itself fixes the template: a callback (`Closure(T): void`), a contravariant generic (`Sink<T>`, or `Callback<T>` below) or an invariant generic (`Box<T>`). A value of type `T` or a `class-string<T>` does not, and neither does later code. The template then gets its default, or else its constraint.

```php
<?php
/** @template-contravariant T = int */
final class IntSink {
    /** @param T $value */
    public function put(mixed $value): void {}
}

/** @template-contravariant T */
final class Logger {
    /** @param T $first */
    public function __construct(mixed $first) {}

    /** @param T $value */
    public function log(mixed $value): void {}
}

/** @template-contravariant T */
final class Callback {
    /** @param Closure(T): void $callback */
    public function __construct(private readonly Closure $callback) {}

    /** @param T $value */
    public function call(mixed $value): void {
        ($this->callback)($value);
    }
}

/** @template-contravariant T */
final class Forwarder {
    /** @param Callback<T> $callback */
    public function __construct(private readonly Callback $callback) {}

    /** @param T $value */
    public function put(mixed $value): void {
        $this->callback->call($value);
    }
}

function f(): void {
    $sink = new IntSink(); // IntSink<int>
    $sink->put('a'); // InvalidScalarArgument

    $logger = new Logger(1); // Logger<mixed>
    $logger->log('a'); // no issue

    $callback = new Callback(function (int $value): void {}); // Callback<int>
    $callback->call('a'); // InvalidScalarArgument

    $forwarder = new Forwarder($callback); // Forwarder<int>
    $forwarder->put('a'); // InvalidScalarArgument
}
```

## Star projection

`Box<*>` is a `Box` of a type that is not known. Kotlin calls it a [star projection](https://kotlinlang.org/docs/generics.html#star-projections), Java an unbounded wildcard (`Box<?>`). Every `Box` fits a `Box<*>` parameter. What you read from a `Box<*>` has the constraint of the template. No value is known to fit what you write to it, so Psalm expects `never` there.

```php
<?php
/** @template T of object */
final class Box {
    /** @param T $value */
    public function __construct(private object $value) {}

    /** @return T */
    public function get(): object {
        return $this->value;
    }

    /** @param T $value */
    public function set(object $value): void {
        $this->value = $value;
    }
}

/** @param Box<*> $box */
function takesAnyBox(Box $box): void {
    $value = $box->get(); // object
    $box->set(new DateTime()); // InvalidArgument
}

/** @param Box<object> $box */
function takesBoxOfObject(Box $box): void {}

/**
 * @param Box<DateTime> $dates
 * @param Box<*> $any
 */
function f(Box $dates, Box $any): void {
    takesAnyBox($dates); // no issue
    takesBoxOfObject($dates); // InvalidArgument
    takesBoxOfObject($any); // ArgumentTypeCoercion
}
```

Psalm also expects `never` for an assigned property, for the parameters of a closure that the object returns, and for a parameter of type `Cell<T>`, as the argument may be any `Cell`. A closure that you pass to the object receives values, so its parameters have the constraint.

```php
<?php
/** @template T of object */
final class Cell {
    /** @var list<T> */
    public array $history = [];

    /** @param T $value */
    public function __construct(public object $value) {}

    /** @return Closure(T): void */
    public function setter(): Closure {
        return function (object $value): void {};
    }

    /** @param Closure(T): void $callback */
    public function each(Closure $callback): void {}

    /** @param Cell<T> $other */
    public function swap(Cell $other): void {}
}

/** @param Cell<*> $cell */
function f(Cell $cell): void {
    $cell->value; // object
    $cell->value = new DateTime(); // InvalidPropertyAssignmentValue
    $cell->history = []; // no issue
    $cell->history[] = new DateTime(); // InvalidPropertyAssignmentValue
    $cell->setter(); // Closure(never): void
    $cell->each(function (object $value): void {}); // no issue
    $cell->swap($cell); // InvalidArgument
}
```

`*` only affects its own type argument: a `Pair<int, *>` reads and writes its `int` as usual. A `Box<*>` fits a parameter written as `Box` without type arguments, and a covariant `Reader<*>` fits the `Reader` of its whole constraint. `class-string<*>` is `class-string` and `list<*>` is `list<mixed>`.

```php
<?php
/** @template T of object */
interface Box {}

/** @template-covariant T of Countable */
interface Reader {}

/**
 * @template TKey
 * @template TValue
 */
final class Pair {
    /**
     * @param TKey $key
     * @param TValue $value
     */
    public function __construct(public mixed $key, public mixed $value) {}
}

function takesBareBox(Box $box): void {}

/** @param Reader<Countable> $reader */
function takesReaderOfCountable(Reader $reader): void {}

/**
 * @param Box<*> $box
 * @param Reader<*> $reader
 * @param Pair<int, *> $pair
 * @param class-string<*> $class
 * @param list<*> $list
 */
function f(Box $box, Reader $reader, Pair $pair, string $class, array $list): void {
    takesBareBox($box); // no issue
    takesReaderOfCountable($reader); // no issue
    $pair->key = 5; // no issue
    $pair->value = 5; // InvalidPropertyAssignmentValue
    $class; // class-string
    $list; // list<mixed>
}
```

Psalm reports [InvalidDocblock](../running_psalm/issues/InvalidDocblock.md) for `*` in `@extends`, `@implements` or `@use`.

## Builtin templated classes and interfaces

Psalm declares templates for many builtin classes and interfaces. Extend or implement them like your own templated classes:

```php
<?php
final class User {}

/** @implements IteratorAggregate<int, User> */
final class Team implements IteratorAggregate {
    /** @param list<User> $members */
    public function __construct(private readonly array $members) {}

    #[Override]
    public function getIterator(): ArrayIterator {
        return new ArrayIterator($this->members);
    }
}

function f(Team $team): void {
    foreach ($team as $id => $member) {
        $id; // int
        $member; // User
    }
}
```

Among them:

- `interface Traversable<TKey, TValue>`
- `interface ArrayAccess<TKey, TValue>`
- `interface IteratorAggregate<TKey, TValue> extends Traversable<TKey, TValue>`
- `interface Iterator<TKey, TValue> extends Traversable<TKey, TValue>`
- `interface SeekableIterator<TKey, TValue> extends Iterator<TKey, TValue>`

- `class Generator<TKey, TValue, TSend, TReturn> implements Iterator<TKey, TValue>`
- `class ArrayObject<TKey of array-key, TValue> implements IteratorAggregate<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class ArrayIterator<TKey of array-key, TValue> implements SeekableIterator<TKey, TValue>, ArrayAccess<TKey, TValue>`
- `class DOMNodeList<TNode of DOMNode> implements Traversable<int, TNode>`
- `class SplDoublyLinkedList<TValue> implements Iterator<int, TValue>, ArrayAccess<int, TValue>`
- `class SplQueue<TValue> extends SplDoublyLinkedList<TValue>`

- `abstract class FilterIterator<TKey, TValue, TIterator>`
