<?php

/** COPIED FROM https://github.com/php-ds/polyfill/blob/f9cac9fcf1698484f9d13071138638876786c1b3/phpstorm-stub.php */

namespace Ds;

use ArrayAccess;
use Countable;
use Ds;
use Error;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use OutOfRangeException;
use Traversable;
use UnderflowException;

/**
 * Collection is the base interface which covers functionality common to all the
 * data structures in this library. It guarantees that all structures are
 * traversable, countable, and can be converted to json using json_encode().
 *
 * @package Ds
 * @template TValue
 *
 * @extends Traversable<mixed, TValue>
 */
interface Collection extends Traversable, Countable, JsonSerializable
{
    /**
     * Removes all values from the collection.
     */
    public function clear();

    /**
     * Returns the size of the collection.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Returns a shallow copy of the collection.
     *
     * @return Collection a copy of the collection.
     */
    public function copy(): Collection;

    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Returns an array representation of the collection.
     *
     * The format of the returned array is implementation-dependent.
     * Some implementations may throw an exception if an array representation
     * could not be created.
     *
     * @return array<TValue>
     */
    public function toArray(): array;
}

/**
 * A Deque (pronounced "deck") is a sequence of values in a contiguous buffer
 * that grows and shrinks automatically. The name is a common abbreviation of
 * "double-ended queue".
 *
 * While a Deque is very similar to a Vector, it offers constant time operations
 * at both ends of the buffer, ie. shift, unshift, push and pop are all O(1).
 *
 * @package Ds
 * @template TValue
 *
 * @implements Sequence<TValue>
 */
final class Deque implements IteratorAggregate, ArrayAccess, Sequence
{
    const MIN_CAPACITY = 8;

    // BEGIN GenericCollection Trait
    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool whether the collection is empty.
     */
    public function isEmpty(): bool
    {
    }

    /**
     * Returns a representation that can be natively converted to JSON, which is
     * called when invoking json_encode.
     *
     * @return mixed the data to be JSON encoded.
     *
     * @see JsonSerializable
     */
    public function jsonSerialize()
    {
    }

    /**
     * Creates a shallow copy of the collection.
     *
     * @return Collection a shallow copy of the collection.
     */
    public function copy(): Collection
    {
    }

    /**
     * Invoked when calling var_dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
    }

    /**
     * Returns a string representation of the collection, which is invoked when
     * the collection is converted to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
    }
    // END GenericCollection Trait

    // BEGIN GenericSequence Trait
    /**
     * @inheritDoc
     */
    public function __construct($values = null)
    {
    }

    /**
     * Returns an array representation of the collection.
     *
     * The format of the returned array is implementation-dependent. Some
     * implementations may throw an exception if an array representation
     * could not be created (for example when object are used as keys).
     *
     * @return array
     */
    public function toArray(): array
    {
    }

    /**
     * @inheritdoc
     */
    public function apply(callable $callback)
    {
    }

    /**
     * @inheritdoc
     */
    public function merge($values): Sequence
    {
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
    }

    /**
     * @inheritDoc
     */
    public function contains(...$values): bool
    {
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $callback = null): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function find($value)
    {
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
    }

    /**
     * @inheritDoc
     */
    public function get(int $index)
    {
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, ...$values)
    {
    }

    /**
     * @inheritDoc
     */
    public function join(string $glue = null): string
    {
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
    }

    /**
     * @inheritDoc
     */
    public function map(callable $callback): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
    }

    /**
     * @inheritDoc
     */
    public function push(...$values)
    {
    }

    /**
     * @inheritDoc
     */
    public function reduce(callable $callback, $initial = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index)
    {
    }

    /**
     * @inheritDoc
     */
    public function reverse()
    {
    }

    /**
     * @inheritDoc
     */
    public function reversed(): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function rotate(int $rotations)
    {
    }

    /**
     * @inheritDoc
     */
    public function set(int $index, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function sorted(callable $comparator = null): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function sum()
    {
    }

    /**
     * @inheritDoc
     */
    public function unshift(...$values)
    {
    }

    /**
     *
     */
    public function getIterator()
    {
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
    }
    // END GenericSequence Trait

    // BEGIN SquaredCapacityTrait
    // BEGIN Capacity Trait
    /**
     * Returns the current capacity.
     *
     * @return int
     */
    public function capacity(): int
    {
    }

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity)
    {
    }

    /**
     * @return float the structures growth factor.
     */
    protected function getGrowthFactor(): float
    {
    }

    /**
     * @return float to multiply by when decreasing capacity.
     */
    protected function getDecayFactor(): float
    {
    }

    /**
     * Checks and adjusts capacity if required.
     */
    protected function checkCapacity()
    {
    }

    /**
     * Called when capacity should be decrease if it drops below a threshold.
     */
    protected function decreaseCapacity()
    {
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldDecreaseCapacity(): bool
    {
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldIncreaseCapacity(): bool
    {
    }
    // END Capacity Trait

    /**
     * Called when capacity should be increased to accommodate new values.
     */
    protected function increaseCapacity()
    {
    }
    // END SquaredCapacityTrait
}

/**
 * Hashable is an interface which allows objects to be used as keys.
 *
 * It’s an alternative to spl_object_hash(), which determines an object’s hash
 * based on its handle: this means that two objects that are considered equal
 * by an implicit definition would not treated as equal because they are not
 * the same instance.
 *
 * @package Ds
 */
interface Hashable
{
    /**
     * Produces a scalar value to be used as the object's hash, which determines
     * where it goes in the hash table. While this value does not have to be
     * unique, objects which are equal must have the same hash value.
     *
     * @return mixed
     */
    public function hash();

    /**
     * Determines if two objects should be considered equal. Both objects will
     * be instances of the same class but may not be the same instance.
     *
     * @param $obj self An instance of the same class to compare to.
     *
     * @return bool
     */
    public function equals($obj): bool;
}

/**
 * A Map is a sequential collection of key-value pairs, almost identical to an
 * array used in a similar context. Keys can be any type, but must be unique.
 *
 * @package Ds
 * @template TKey
 * @template TValue
 *
 * @implements Collection<TValue>
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
final class Map implements IteratorAggregate, ArrayAccess, Collection
{
    const MIN_CAPACITY = 8;

    // BEGIN GenericCollection Trait
    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool whether the collection is empty.
     */
    public function isEmpty(): bool
    {
    }

    /**
     * Returns a representation that can be natively converted to JSON, which is
     * called when invoking json_encode.
     *
     * @return mixed the data to be JSON encoded.
     *
     * @see JsonSerializable
     */
    public function jsonSerialize()
    {
    }

    /**
     * Creates a shallow copy of the collection.
     *
     * @return Collection a shallow copy of the collection.
     */
    public function copy(): Collection
    {
    }

    /**
     * Returns a string representation of the collection, which is invoked when
     * the collection is converted to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
    }
    // END GenericCollection Trait

    // BEGIN SquaredCapacityTrait
    // BEGIN Capacity Trait
    /**
     * Returns the current capacity.
     *
     * @return int
     */
    public function capacity(): int
    {
    }

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity)
    {
    }

    /**
     * @return float the structures growth factor.
     */
    protected function getGrowthFactor(): float
    {
    }

    /**
     * @return float to multiply by when decreasing capacity.
     */
    protected function getDecayFactor(): float
    {
    }

    /**
     * Checks and adjusts capacity if required.
     */
    protected function checkCapacity()
    {
    }

    /**
     * Called when capacity should be decrease if it drops below a threshold.
     */
    protected function decreaseCapacity()
    {
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldDecreaseCapacity(): bool
    {
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldIncreaseCapacity(): bool
    {
    }
    // END Capacity Trait

    /**
     * Called when capacity should be increased to accommodate new values.
     */
    protected function increaseCapacity()
    {
    }
    // END SquaredCapacityTrait

    /**
     * Creates a new instance.
     *
     * @param array<TKey, TValue>|Traversable<TKey, TValue>|null $values
     */
    public function __construct($values = null)
    {
    }

    /**
     * Updates all values by applying a callback function to each value.
     *
     * @param callable(TKey, TValue):TValue $callback Accepts two arguments: key and value, should
     *                           return what the updated value will be.
     */
    public function apply(callable $callback)
    {
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
    }

    /**
     * Return the first Pair from the Map
     *
     * @return Pair<TKey, TValue>
     *
     * @throws UnderflowException
     */
    public function first(): Pair
    {
    }

    /**
     * Return the last Pair from the Map
     *
     * @return Pair<TKey, TValue>
     *
     * @throws UnderflowException
     */
    public function last(): Pair
    {
    }

    /**
     * Return the pair at a specified position in the Map
     *
     * @param int $position
     *
     * @return Pair<TKey, TValue>
     *
     * @throws OutOfRangeException
     */
    public function skip(int $position): Pair
    {
    }

    /**
     * Returns the result of associating all keys of a given traversable object
     * or array with their corresponding values, as well as those of this map.
     *
     * @param array<TKey, TValue>|Traversable<TKey, TValue> $values
     *
     * @return Map<TKey, TValue>
     */
    public function merge($values): Map
    {
    }

    /**
     * Creates a new map containing the pairs of the current instance whose keys
     * are also present in the given map. In other words, returns a copy of the
     * current map with all keys removed that are not also in the other map.
     *
     * @param Map<TKey, TValue> $map The other map.
     *
     * @return Map<TKey, TValue> A new map containing the pairs of the current instance
     *                 whose keys are also present in the given map. In other
     *                 words, returns a copy of the current map with all keys
     *                 removed that are not also in the other map.
     */
    public function intersect(Map $map): Map
    {
    }

    /**
     * Returns the result of removing all keys from the current instance that
     * are present in a given map.
     *
     * @param Map<TKey, TValue> $map The map containing the keys to exclude.
     *
     * @return Map<TKey, TValue> The result of removing all keys from the current instance
     *                 that are present in a given map.
     */
    public function diff(Map $map): Map
    {
    }

    /**
     * Returns whether an association a given key exists.
     *
     * @param TKey $key
     *
     * @return bool
     */
    public function hasKey($key): bool
    {
        return $this->lookupKey($key) !== null;
    }

    /**
     * Returns whether an association for a given value exists.
     *
     * @param TValue $value
     *
     * @return bool
     */
    public function hasValue($value): bool
    {
        return $this->lookupValue($value) !== null;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->pairs);
    }

    /**
     * Returns a new map containing only the values for which a predicate
     * returns true. A boolean test will be used if a predicate is not provided.
     *
     * @param callable(TKey, TValue):bool|null $callback Accepts a key and a value, and returns:
     *                                true : include the value,
     *                                false: skip the value.
     *
     * @return Map<TKey, TValue>
     */
    public function filter(callable $callback = null): Map
    {
        $filtered = new self();

        foreach ($this as $key => $value) {
            if ($callback ? $callback($key, $value) : $value) {
                $filtered->put($key, $value);
            }
        }

        return $filtered;
    }

    /**
     * Returns the value associated with a key, or an optional default if the
     * key is not associated with a value.
     * @template TDefault
     *
     * @param TKey $key
     * @param TDefault $default
     *
     * @return TValue|TDefault The associated value or fallback default if provided.
     *
     * @throws OutOfBoundsException if no default was provided and the key is
     *                               not associated with a value.
     */
    public function get($key, $default = null)
    {
        if (($pair = $this->lookupKey($key))) {
            return $pair->value;
        }

        // Check if a default was provided.
        if (func_num_args() === 1) {
            throw new OutOfBoundsException();
        }

        return $default;
    }

    /**
     * Returns a set of all the keys in the map.
     *
     * @return Set<TKey>
     */
    public function keys(): Set
    {
        $key = function($pair) {
            return $pair->key;
        };

        return new Set(array_map($key, $this->pairs));
    }

    /**
     * Returns a new map using the results of applying a callback to each value.
     *
     * The keys will be equal in both maps.
     *
     * @param callable $callback Accepts two arguments: key and value, should
     *                           return what the updated value will be.
     *
     * @return Map<TKey, TValue>
     */
    public function map(callable $callback): Map
    {
        $apply = function($pair) use ($callback) {
            return $callback($pair->key, $pair->value);
        };

        return new self(array_map($apply, $this->pairs));
    }

    /**
     * Returns a sequence of pairs representing all associations.
     *
     * @return Sequence<Pair<TKey, TValue>>
     */
    public function pairs(): Sequence
    {
        $copy = function($pair) {
            return $pair->copy();
        };

        return new Vector(array_map($copy, $this->pairs));
    }

    /**
     * Associates a key with a value, replacing a previous association if there
     * was one.
     *
     * @param TKey $key
     * @param TValue $value
     */
    public function put($key, $value)
    {
        $pair = $this->lookupKey($key);

        if ($pair) {
            $pair->value = $value;

        } else {
            $this->checkCapacity();
            $this->pairs[] = new Pair($key, $value);
        }
    }

    /**
     * Creates associations for all keys and corresponding values of either an
     * array or iterable object.
     *
     * @param Traversable<TKey, TValue>|array<TKey, TValue> $values
     */
    public function putAll($values)
    {
    }

    /**
     * Iteratively reduces the map to a single value using a callback.
     *
     * @param callable $callback Accepts the carry, key, and value, and
     *                           returns an updated carry value.
     *
     * @param mixed|null $initial Optional initial carry value.
     *
     * @return mixed The carry value of the final iteration, or the initial
     *               value if the map was empty.
     */
    public function reduce(callable $callback, $initial = null)
    {
    }

    /**
     * Removes a key's association from the map and returns the associated value
     * or a provided default if provided.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed The associated value or fallback default if provided.
     *
     * @throws \OutOfBoundsException if no default was provided and the key is
     *                               not associated with a value.
     */
    public function remove($key, $default = null)
    {
    }

    /**
     * Sorts the map into the reversed order.
     */
    public function reverse()
    {
    }

    /**
     * Returns a reversed copy of the map.
     *
     * @return Map<TKey, TValue>
     */
    public function reversed(): Map
    {
    }

    /**
     * Returns a sub-sequence of a given length starting at a specified offset.
     *
     * @param int $offset      If the offset is non-negative, the map will
     *                         start at that offset in the map. If offset is
     *                         negative, the map will start that far from the
     *                         end.
     *
     * @param int|null $length If a length is given and is positive, the
     *                         resulting set will have up to that many pairs in
     *                         it. If the requested length results in an
     *                         overflow, only pairs up to the end of the map
     *                         will be included.
     *
     *                         If a length is given and is negative, the map
     *                         will stop that many pairs from the end.
     *
     *                        If a length is not provided, the resulting map
     *                        will contains all pairs between the offset and
     *                        the end of the map.
     *
     * @return Map
     */
    public function slice(int $offset, int $length = null): Map
    {
    }

    /**
     * Sorts the map in-place, based on an optional callable comparator.
     *
     * The map will be sorted by value.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     */
    public function sort(callable $comparator = null)
    {
    }

    /**
     * Returns a sorted copy of the map, based on an optional callable
     * comparator. The map will be sorted by value.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *
     * @return Map
     */
    public function sorted(callable $comparator = null): Map
    {
    }

    /**
     * Sorts the map in-place, based on an optional callable comparator.
     *
     * The map will be sorted by key.
     *
     * @param callable|null $comparator Accepts two keys to be compared.
     */
    public function ksort(callable $comparator = null)
    {
    }

    /**
     * Returns a sorted copy of the map, based on an optional callable
     * comparator. The map will be sorted by key.
     *
     * @param callable|null $comparator Accepts two keys to be compared.
     *
     * @return Map
     */
    public function ksorted(callable $comparator = null): Map
    {
    }

    /**
     * Returns the sum of all values in the map.
     *
     * @return int|float The sum of all the values in the map.
     */
    public function sum()
    {
    }

    /**
     * Returns an array representation of the collection.
     *
     * The format of the returned array is implementation-dependent. Some
     * implementations may throw an exception if an array representation
     * could not be created (for example when object are used as keys).
     *
     * @return array
     */
    public function toArray(): array
    {
    }

    /**
     * Returns a sequence of all the associated values in the Map.
     *
     * @return Sequence<TValue>
     */
    public function values(): Sequence
    {
    }

    /**
     * Creates a new map that contains the pairs of the current instance as well
     * as the pairs of another map.
     *
     * @param Map $map The other map, to combine with the current instance.
     *
     * @return Map A new map containing all the pairs of the current
     *                 instance as well as another map.
     */
    public function union(Map $map): Map
    {
    }

    /**
     * Creates a new map using keys of either the current instance or of another
     * map, but not of both.
     *
     * @param Map $map
     *
     * @return Map A new map containing keys in the current instance as well
     *                 as another map, but not in both.
     */
    public function xor(Map $map): Map
    {
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
    }

    /**
     * Returns a representation to be used for var_dump and print_r.
     */
    public function __debugInfo()
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * @inheritdoc
     *
     * @throws OutOfBoundsException
     */
    public function &offsetGet($offset)
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
    }
}

/**
 * A pair which represents a key and an associated value.
 *
 * @package Ds
 * @template TKey
 * @template TValue
 */
final class Pair implements JsonSerializable
{
    /**
     * @param TKey $key The pair's key
     */
    public $key;

    /**
     * @param TValue $value The pair's value
     */
    public $value;

    /**
     * Creates a new instance.
     *
     * @param TKey $key
     * @param TValue $value
     */
    public function __construct($key = null, $value = null)
    {
    }

    /**
     * This allows unset($pair->key) to not completely remove the property,
     * but be set to null instead.
     *
     * @param mixed $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
    }

    /**
     * Returns a copy of the Pair
     *
     * @return Pair<TKey, TValue>
     */
    public function copy(): Pair
    {
    }

    /**
     * Returns a representation to be used for var_dump and print_r.
     *
     * @return array
     */
    public function __debugInfo()
    {
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
    }

    /**
     * Returns a string representation of the pair.
     *
     * @return string a string representation of the pair.
     */
    public function __toString(): string
    {
    }
}

/**
 * Describes the behaviour of values arranged in a single, linear dimension.
 * Some languages refer to this as a "List". It’s similar to an array that uses
 * incremental integer keys, with the exception of a few characteristics:
 *
 *  - Values will always be indexed as [0, 1, 2, …, size - 1].
 *  - Only allowed to access values by index in the range [0, size - 1].
 *
 * @package Ds
 * @template TValue
 * @extends Collection<TValue>
 */
interface Sequence extends Collection
{
    /**
     * Ensures that enough memory is allocated for a required capacity.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity);

    /**
     * Updates every value in the sequence by applying a callback, using the
     * return value as the new value.
     *
     * @param callable(TValue) : TValue $callback Accepts the value, returns the new value.
     */
    public function apply(callable $callback);

    /**
     * Returns the current capacity of the sequence.
     *
     * @return int
     */
    public function capacity(): int;

    /**
     * Determines whether the sequence contains all of zero or more values.
     *
     * @param TValue ...$values
     *
     * @return bool true if at least one value was provided and the sequence
     *              contains all given values, false otherwise.
     */
    public function contains(...$values): bool;

    /**
     * Returns a new sequence containing only the values for which a callback
     * returns true. A boolean test will be used if a callback is not provided.
     *
     * @param callable(TValue):bool|null $callback Accepts a value, returns a boolean result:
     *                                true : include the value,
     *                                false: skip the value.
     *
     * @return Sequence<TValue>
     */
    public function filter(callable $callback = null): Sequence;

    /**
     * Returns the index of a given value, or false if it could not be found.
     *
     * @param mixed $value
     *
     * @return int|bool
     */
    public function find($value);

    /**
     * Returns the first value in the sequence.
     *
     * @return TValue
     *
     * @throws \UnderflowException if the sequence is empty.
     */
    public function first();

    /**
     * Returns the value at a given index (position) in the sequence.
     *
     * @param int $index
     *
     * @return TValue
     *
     * @throws \OutOfRangeException if the index is not in the range [0, size-1]
     */
    public function get(int $index);

    /**
     * Inserts zero or more values at a given index.
     *
     * Each value after the index will be moved one position to the right.
     * Values may be inserted at an index equal to the size of the sequence.
     *
     * @param int   $index
     * @param TValue ...$values
     *
     * @throws \OutOfRangeException if the index is not in the range [0, n]
     */
    public function insert(int $index, ...$values);

    /**
     * Joins all values of the sequence into a string, adding an optional 'glue'
     * between them. Returns an empty string if the sequence is empty.
     *
     * @param string $glue
     *
     * @return string
     */
    public function join(string $glue = null): string;

    /**
     * Returns the last value in the sequence.
     *
     * @return TValue
     *
     * @throws \UnderflowException if the sequence is empty.
     */
    public function last();

    /**
     * Returns a new sequence using the results of applying a callback to each
     * value.
     * @template TNewValue
     *
     * @param callable(TValue):TNewValue $callback
     *
     * @return Sequence<TNewValue>
     */
    public function map(callable $callback): Sequence;

    /**
     * Returns the result of adding all given values to the sequence.
     *
     * @param array<TValue>|Traversable<TValue> $values
     *
     * @return Sequence<TValue>
     */
    public function merge($values): Sequence;

    /**
     * Removes the last value in the sequence, and returns it.
     *
     * @return mixed what was the last value in the sequence.
     *
     * @throws \UnderflowException if the sequence is empty.
     */
    public function pop();

    /**
     * Adds zero or more values to the end of the sequence.
     *
     * @param TValue ...$values
     */
    public function push(...$values);

    /**
     * Iteratively reduces the sequence to a single value using a callback.
     *
     * @param callable $callback Accepts the carry and current value, and
     *                           returns an updated carry value.
     *
     * @param mixed|null $initial Optional initial carry value.
     *
     * @return mixed The carry value of the final iteration, or the initial
     *               value if the sequence was empty.
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * Removes and returns the value at a given index in the sequence.
     *
     * @param int $index this index to remove.
     *
     * @return TValue the removed value.
     *
     * @throws \OutOfRangeException if the index is not in the range [0, size-1]
     */
    public function remove(int $index);

    /**
     * Reverses the sequence in-place.
     */
    public function reverse();

    /**
     * Returns a reversed copy of the sequence.
     *
     * @return Sequence
     */
    public function reversed();

    /**
     * Rotates the sequence by a given number of rotations, which is equivalent
     * to successive calls to 'shift' and 'push' if the number of rotations is
     * positive, or 'pop' and 'unshift' if negative.
     *
     * @param int $rotations The number of rotations (can be negative).
     */
    public function rotate(int $rotations);

    /**
     * Replaces the value at a given index in the sequence with a new value.
     *
     * @param int   $index
     * @param TValue $value
     *
     * @throws \OutOfRangeException if the index is not in the range [0, size-1]
     */
    public function set(int $index, $value);

    /**
     * Removes and returns the first value in the sequence.
     *
     * @return TValue what was the first value in the sequence.
     *
     * @throws \UnderflowException if the sequence was empty.
     */
    public function shift();

    /**
     * Returns a sub-sequence of a given length starting at a specified index.
     *
     * @param int $index  If the index is positive, the sequence will start
     *                    at that index in the sequence. If index is negative,
     *                    the sequence will start that far from the end.
     *
     * @param int $length If a length is given and is positive, the resulting
     *                    sequence will have up to that many values in it.
     *                    If the length results in an overflow, only values
     *                    up to the end of the sequence will be included.
     *
     *                    If a length is given and is negative, the sequence
     *                    will stop that many values from the end.
     *
     *                    If a length is not provided, the resulting sequence
     *                    will contain all values between the index and the
     *                    end of the sequence.
     *
     * @return Sequence<TValue>
     */
    public function slice(int $index, int $length = null): Sequence;

    /**
     * Sorts the sequence in-place, based on an optional callable comparator.
     *
     * @param callable(TValue, TValue):int|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     */
    public function sort(callable $comparator = null);

    /**
     * Returns a sorted copy of the sequence, based on an optional callable
     * comparator. Natural ordering will be used if a comparator is not given.
     *
     * @param callable|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     *
     * @return Sequence<TValue>
     */
    public function sorted(callable $comparator = null): Sequence;

    /**
     * Returns the sum of all values in the sequence.
     *
     * @return int|float The sum of all the values in the sequence.
     */
    public function sum();

    /**
     * Adds zero or more values to the front of the sequence.
     *
     * @param TValue ...$values
     */
    public function unshift(...$values);
}


/**
 * A Vector is a sequence of values in a contiguous buffer that grows and
 * shrinks automatically. It’s the most efficient sequential structure because
 * a value’s index is a direct mapping to its index in the buffer, and the
 * growth factor isn't bound to a specific multiple or exponent.
 *
 * @package Ds
 * @template TValue
 * @implements Sequence<TValue>
 * @implements IteratorAggregate<int, TValue>
 */
final class Vector implements IteratorAggregate, ArrayAccess, Sequence
{
    const MIN_CAPACITY = 8;

    // BEGIN GenericCollection Trait
    /**
     * Returns whether the collection is empty.
     *
     * This should be equivalent to a count of zero, but is not required.
     * Implementations should define what empty means in their own context.
     *
     * @return bool whether the collection is empty.
     */
    public function isEmpty(): bool
    {
    }

    /**
     * Returns a representation that can be natively converted to JSON, which is
     * called when invoking json_encode.
     *
     * @return mixed the data to be JSON encoded.
     *
     * @see JsonSerializable
     */
    public function jsonSerialize()
    {
    }

    /**
     * Creates a shallow copy of the collection.
     *
     * @return Collection<TValue> a shallow copy of the collection.
     */
    public function copy(): Collection
    {
    }

    /**
     * Invoked when calling var_dump.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
    }

    /**
     * Returns a string representation of the collection, which is invoked when
     * the collection is converted to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
    }
    // END GenericCollection Trait

    // BEGIN GenericSequence Trait
    /**
     * @inheritDoc
     */
    public function __construct($values = null)
    {
    }

    /**
     * Returns an array representation of the collection.
     *
     * The format of the returned array is implementation-dependent. Some
     * implementations may throw an exception if an array representation
     * could not be created (for example when object are used as keys).
     *
     * @return array
     */
    public function toArray(): array
    {
    }

    /**
     * @inheritdoc
     */
    public function apply(callable $callback)
    {
    }

    /**
     * @inheritdoc
     */
    public function merge($values): Sequence
    {
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
    }

    /**
     * @inheritDoc
     */
    public function contains(...$values): bool
    {
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $callback = null): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function find($value)
    {
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
    }

    /**
     * @inheritDoc
     */
    public function get(int $index)
    {
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, ...$values)
    {
    }

    /**
     * @inheritDoc
     */
    public function join(string $glue = null): string
    {
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
    }

    /**
     * @inheritDoc
     */
    public function map(callable $callback): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
    }

    /**
     * @inheritDoc
     */
    public function push(...$values)
    {
    }

    /**
     * @inheritDoc
     */
    public function reduce(callable $callback, $initial = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index)
    {
    }

    /**
     * @inheritDoc
     */
    public function reverse()
    {
    }

    /**
     * @inheritDoc
     */
    public function reversed(): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function rotate(int $rotations)
    {
    }

    /**
     * @inheritDoc
     */
    public function set(int $index, $value)
    {
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function sorted(callable $comparator = null): Sequence
    {
    }

    /**
     * @inheritDoc
     */
    public function sum()
    {
    }

    /**
     * @inheritDoc
     */
    public function unshift(...$values)
    {
    }

    /**
     *
     */
    public function getIterator()
    {
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
    }
    // END GenericSequence Trait

    // BEGIN Capacity Trait
    /**
     * Returns the current capacity.
     *
     * @return int
     */
    public function capacity(): int
    {
    }

    /**
     * Ensures that enough memory is allocated for a specified capacity. This
     * potentially reduces the number of reallocations as the size increases.
     *
     * @param int $capacity The number of values for which capacity should be
     *                      allocated. Capacity will stay the same if this value
     *                      is less than or equal to the current capacity.
     */
    public function allocate(int $capacity)
    {
    }

    /**
     * @return float the structures growth factor.
     */
    protected function getGrowthFactor(): float
    {
    }

    /**
     * @return float to multiply by when decreasing capacity.
     */
    protected function getDecayFactor(): float
    {
    }

    /**
     * @return float the ratio between size and capacity when capacity should be
     *               decreased.
     */
    protected function getTruncateThreshold(): float
    {
    }

    /**
     * Checks and adjusts capacity if required.
     */
    protected function checkCapacity()
    {
    }

    /**
     * Called when capacity should be increased to accommodate new values.
     */
    protected function increaseCapacity()
    {
    }

    /**
     * Called when capacity should be decrease if it drops below a threshold.
     */
    protected function decreaseCapacity()
    {
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldDecreaseCapacity(): bool
    {
    }

    /**
     * @return bool whether capacity should be increased.
     */
    protected function shouldIncreaseCapacity(): bool
    {
    }
    // END Capacity Trait
}

/**
 * A Set is a sequence of unique values. This implementation uses
 * the same hash table as Ds\Map, where values are used as keys and
 * the mapped value is ignored.
 *
 * @package Ds
 * @template TValue
 * @implements Collection<TValue>
 * @implements ArrayAccess<int, TValue>
 * @implements Traversable<int, TValue>
 */
final class Set implements ArrayAccess, Collection, Traversable {
    /**
     * Adds all given values to the set that haven't already been added.
     *
     * @param TValue ...$values
     */
    public function add(...$values): void
    {
    }

    /**
     * Creates a new instance.
     *
     * @param array<TValue>|Traversable<TValue> $values A traversable object
     *      or an array to use for the initial values.
     */
    public function __construct($values = null)
    {
    }

    /**
     * Determines if the set contains all values.
     *
     * @param TValue ...$values
     */
    public function contains(...$values): bool
    {
    }

    /**
     * Returns a shallow copy of the set.
     *
     * @return Set<TValue>
     */
    public function copy(): Set
    {
    }

    /**
     * Creates a new set using values that aren't in another set.
     *
     * @param Set<TValue> $set
     *
     * @return Set<TValue>
     */
    public function diff(Set $set): Set
    {
    }

    /**
     * Creates a new set using a callable to determine which values to include.
     *
     * @param callable(TValue):bool|null $callback
     *
     * @return Set<TValue> A new set containing all the values for which either
     *      the callback returned TRUE, or all values that convert to TRUE if a
     *      callback was not provided.
     */
    public function filter(callable $callback = null): Set
    {
    }

    /**
     * Returns the first value in the set.
     *
     * @return TValue
     */
    public function first()
    {
    }

    /**
     * Returns the value at a given index.
     *
     * @return TValue
     */
    public function get(int $index)
    {
    }

    /**
     * Creates a new set using values common to both the current instance and
     * another set. In other words, returns a copy of the current instance with
     * all values removed that are not in the other set.
     *
     * @param Set<TValue> $set
     *
     * @return Set<TValue>
     */
    public function intersect(Set $set): Set
    {
    }

    /**
     * Returns the last value in the set.
     *
     * @return TValue
     */
    public function last()
    {
    }

    /**
     * Returns the result of adding all given values to the set.
     *
     * @param array<TValue>|Traversable<TValue> $values
     *
     * @return Set<TValue>
     */
    public function merge($values): Set
    {
    }

    /**
     * Removes all given values from the set, ignoring any that are not in the set.
     *
     * @param TValue ...$values
     */
    public function remove(...$values): void
    {
    }

    /**
     * Returns a reversed copy of the set.
     *
     * @return Set<TValue>
     */
    public function reversed(): Set
    {
    }

    /**
     * Creates a sub-set of a given range.
     *
     * @return Set<TValue>
     */
    public function slice(int $index, int $length = null): Set
    {
    }

    /**
     * Sorts the set in-place, based on an optional comparator function.
     *
     * @param callable(TValue, TValue):int|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     */
    public function sort(callable $comparator = null): void
    {
    }

    /**
     * Returns a sorted copy, using an optional comparator function.
     *
     * @param callable(TValue, TValue):int|null $comparator Accepts two values to be compared.
     *                                  Should return the result of a <=> b.
     *
     * @return Set<TValue>
     */
    public function sorted(callable $comparator = null): Set
    {
    }

    /**
     * Converts the set to an array.
     *
     * @return array<TValue> An array containing all the values in the same order as the set.
     */
    public function toArray(): array
    {
    }

    /**
     * Creates a new set that contains the values of the current instance as
     * well as the values of another set.
     *
     * @param Set<TValue> $set
     *
     * @return Set<TValue>
     */
    public function union(Set $set): Set
    {
    }

    /**
     * Creates a new set containing values in the current instance as well as
     * another set, but not in both.
     *
     * @param Set<TValue> $set
     *
     * @return Set<TValue>
     */
    public function xor(Set $set): Set
    {
    }
}
