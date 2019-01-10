<?php

/**
 * Interface to detect if a class is traversable using &foreach;.
 * @link http://php.net/manual/en/class.traversable.php
 *
 * @template TKey
 * @template TValue
 */
interface Traversable {
}

/**
 * Interface to create an external Iterator.
 * @link http://php.net/manual/en/class.iteratoraggregate.php
 *
 * @template TKey
 * @template TValue
 *
 * @template-extends Traversable<TKey, TValue>
 */
interface IteratorAggregate extends Traversable {

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable<TKey, TValue> An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator();
}

/**
 * Interface for external iterators or objects that can be iterated
 * themselves internally.
 * @link http://php.net/manual/en/class.iterator.php
 *
 * @template TKey
 * @template TValue
 *
 * @template-extends Traversable<TKey, TValue>
 */
interface Iterator extends Traversable {

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return TValue Can return any type.
     * @since 5.0.0
     */
    public function current();

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next();

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return TKey scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key();

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid();

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind();
}

/**
 * @template TKey
 * @template TValue
 * @template TSend
 * @template TReturn
 *
 * @template-implements Traversable<TKey, TValue>
 */
class Generator implements Traversable {
    /**
     * @return TValue Can return any type.
     */
    public function current() {}

    /**
     * @return void Any returned value is ignored.
     */
    public function next() {}

    /**
     * @return TKey scalar on success, or null on failure.
     */
    public function key() {}

    /**
     * @return bool The return value will be casted to boolean and then evaluated.
     */
    public function valid() {}

    /**
     * @return void Any returned value is ignored.
     */
    public function rewind() {}

    /**
     * @return TReturn Can return any type.
     */
    public function getReturn() {}

    /**
     * @param TSend $value
     * @return TValue Can return any type.
     */
    public function send($value) {}

    public function throw(Exception $exception) {}
}

/**
 * Interface to provide accessing objects as arrays.
 * @link http://php.net/manual/en/class.arrayaccess.php
 *
 * @template TKey
 * @template TValue
 */
interface ArrayAccess {

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param TKey $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset);

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param TKey $offset <p>
     * The offset to retrieve.
     * </p>
     * @return TValue Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset);

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param TKey $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param TValue $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value);

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param TKey $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset);
}

/**
 * This class allows objects to work as arrays.
 * @link http://php.net/manual/en/class.arrayobject.php
 *
 * @template TKey
 * @template TValue
 */
class ArrayObject implements IteratorAggregate, Traversable, ArrayAccess, Serializable, Countable {
    /**
     * Properties of the object have their normal functionality when accessed as list (var_dump, foreach, etc.).
     */
    const STD_PROP_LIST = 1;
    /**
     * Entries can be accessed as properties (read and write).
     */
    const ARRAY_AS_PROPS = 2;
    /**
     * Construct a new array object
     * @link http://php.net/manual/en/arrayobject.construct.php
     * @param array<TKey, TValue>|object $input The input parameter accepts an array or an Object.
     * @param int $flags Flags to control the behaviour of the ArrayObject object.
     * @param string $iterator_class Specify the class that will be used for iteration of the ArrayObject object. ArrayIterator is the default class used.
     * @since 5.0.0
     *
     */
    public function __construct($input = null, $flags = 0, $iterator_class = "ArrayIterator") { }
    /**
     * Returns whether the requested index exists
     * @link http://php.net/manual/en/arrayobject.offsetexists.php
     * @param TKey $index <p>
     * The index being checked.
     * </p>
     * @return bool true if the requested index exists, otherwise false
     * @since 5.0.0
     */
    public function offsetExists($index) { }
    /**
     * Returns the value at the specified index
     * @link http://php.net/manual/en/arrayobject.offsetget.php
     * @param TKey $index <p>
     * The index with the value.
     * </p>
     * @return TValue The value at the specified index or false.
     * @since 5.0.0
     */
    public function offsetGet($index) { }
    /**
     * Sets the value at the specified index to newval
     * @link http://php.net/manual/en/arrayobject.offsetset.php
     * @param TKey $index <p>
     * The index being set.
     * </p>
     * @param TValue $newval <p>
     * The new value for the <i>index</i>.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($index, $newval) { }
    /**
     * Unsets the value at the specified index
     * @link http://php.net/manual/en/arrayobject.offsetunset.php
     * @param TKey $index <p>
     * The index being unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($index) { }
    /**
     * Appends the value
     * @link http://php.net/manual/en/arrayobject.append.php
     * @param TValue $value <p>
     * The value being appended.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function append($value) { }
    /**
     * Creates a copy of the ArrayObject.
     * @link http://php.net/manual/en/arrayobject.getarraycopy.php
     * @return array<TKey, TValue> a copy of the array. When the <b>ArrayObject</b> refers to an object
     * an array of the public properties of that object will be returned.
     * @since 5.0.0
     */
    public function getArrayCopy() { }
    /**
     * Get the number of public properties in the ArrayObject
     * When the <b>ArrayObject</b> is constructed from an array all properties are public.
     * @link http://php.net/manual/en/arrayobject.count.php
     * @return int The number of public properties in the ArrayObject.
     * @since 5.0.0
     */
    public function count() { }
    /**
     * Gets the behavior flags.
     * @link http://php.net/manual/en/arrayobject.getflags.php
     * @return int the behavior flags of the ArrayObject.
     * @since 5.1.0
     */
    public function getFlags() { }
    /**
     * Sets the behavior flags.
     * @link http://php.net/manual/en/arrayobject.setflags.php
     * @param int $flags <p>
     * The new ArrayObject behavior.
     * It takes on either a bitmask, or named constants. Using named
     * constants is strongly encouraged to ensure compatibility for future
     * versions.
     * </p>
     * <p>
     * The available behavior flags are listed below. The actual
     * meanings of these flags are described in the
     * predefined constants.
     * <table>
     * ArrayObject behavior flags
     * <tr valign="top">
     * <td>value</td>
     * <td>constant</td>
     * </tr>
     * <tr valign="top">
     * <td>1</td>
     * <td>
     * ArrayObject::STD_PROP_LIST
     * </td>
     * </tr>
     * <tr valign="top">
     * <td>2</td>
     * <td>
     * ArrayObject::ARRAY_AS_PROPS
     * </td>
     * </tr>
     * </table>
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function setFlags($flags) { }
    /**
     * Sort the entries by value
     * @link http://php.net/manual/en/arrayobject.asort.php
     * @return void
     * @since 5.2.0
     */
    public function asort() { }
    /**
     * Sort the entries by key
     * @link http://php.net/manual/en/arrayobject.ksort.php
     * @return void
     * @since 5.2.0
     */
    public function ksort() { }
    /**
     * Sort the entries with a user-defined comparison function and maintain key association
     * @link http://php.net/manual/en/arrayobject.uasort.php
     * @param callback $cmp_function <p>
     * Function <i>cmp_function</i> should accept two
     * parameters which will be filled by pairs of entries.
     * The comparison function must return an integer less than, equal
     * to, or greater than zero if the first argument is considered to
     * be respectively less than, equal to, or greater than the
     * second.
     * </p>
     * @return void
     * @since 5.2.0
     */
    public function uasort($cmp_function) { }
    /**
     * Sort the entries by keys using a user-defined comparison function
     * @link http://php.net/manual/en/arrayobject.uksort.php
     * @param callback $cmp_function <p>
     * The callback comparison function.
     * </p>
     * <p>
     * Function <i>cmp_function</i> should accept two
     * parameters which will be filled by pairs of entry keys.
     * The comparison function must return an integer less than, equal
     * to, or greater than zero if the first argument is considered to
     * be respectively less than, equal to, or greater than the
     * second.
     * </p>
     * @return void
     * @since 5.2.0
     */
    public function uksort($cmp_function) { }
    /**
     * Sort entries using a "natural order" algorithm
     * @link http://php.net/manual/en/arrayobject.natsort.php
     * @return void
     * @since 5.2.0
     */
    public function natsort() { }
    /**
     * Sort an array using a case insensitive "natural order" algorithm
     * @link http://php.net/manual/en/arrayobject.natcasesort.php
     * @return void
     * @since 5.2.0
     */
    public function natcasesort() { }
    /**
     * Unserialize an ArrayObject
     * @link http://php.net/manual/en/arrayobject.unserialize.php
     * @param string $serialized <p>
     * The serialized <b>ArrayObject</b>.
     * </p>
     * @return void The unserialized <b>ArrayObject</b>.
     * @since 5.3.0
     */
    public function unserialize($serialized) { }
    /**
     * Serialize an ArrayObject
     * @link http://php.net/manual/en/arrayobject.serialize.php
     * @return string The serialized representation of the <b>ArrayObject</b>.
     * @since 5.3.0
     */
    public function serialize() { }
    /**
     * Create a new iterator from an ArrayObject instance
     * @link http://php.net/manual/en/arrayobject.getiterator.php
     * @return ArrayIterator<TKey, TValue> An iterator from an <b>ArrayObject</b>.
     * @since 5.0.0
     */
    public function getIterator() { }
    /**
     * Exchange the array for another one.
     * @link http://php.net/manual/en/arrayobject.exchangearray.php
     * @param mixed $input <p>
     * The new array or object to exchange with the current array.
     * </p>
     * @return array the old array.
     * @since 5.1.0
     */
    public function exchangeArray($input) { }
    /**
     * Sets the iterator classname for the ArrayObject.
     * @link http://php.net/manual/en/arrayobject.setiteratorclass.php
     * @param string $iterator_class <p>
     * The classname of the array iterator to use when iterating over this object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function setIteratorClass($iterator_class) { }
    /**
     * Gets the iterator classname for the ArrayObject.
     * @link http://php.net/manual/en/arrayobject.getiteratorclass.php
     * @return string the iterator class name that is used to iterate over this object.
     * @since 5.1.0
     */
    public function getIteratorClass() { }
}

/**
 * This iterator allows to unset and modify values and keys while iterating
 * over Arrays and Objects.
 * @link http://php.net/manual/en/class.arrayiterator.php
 *
 * @template TValue
 */
class ArrayIterator implements SeekableIterator, ArrayAccess, Serializable, Countable {
    const STD_PROP_LIST = 1;
    const ARRAY_AS_PROPS = 2;
    /**
     * Construct an ArrayIterator
     * @link http://php.net/manual/en/arrayiterator.construct.php
     * @param array $array The array or object to be iterated on.
     * @param int $flags Flags to control the behaviour of the ArrayObject object.
     * @see ArrayObject::setFlags()
     * @since 5.0.0
     */
    public function __construct($array = array(), $flags = 0) { }
    /**
     * Check if offset exists
     * @link http://php.net/manual/en/arrayiterator.offsetexists.php
     * @param string $index <p>
     * The offset being checked.
     * </p>
     * @return bool true if the offset exists, otherwise false
     * @since 5.0.0
     */
    public function offsetExists($index) { }
    /**
     * Get value for an offset
     * @link http://php.net/manual/en/arrayiterator.offsetget.php
     * @param string $index <p>
     * The offset to get the value from.
     * </p>
     * @return TValue The value at offset <i>index</i>.
     * @since 5.0.0
     */
    public function offsetGet($index) { }
    /**
     * Set value for an offset
     * @link http://php.net/manual/en/arrayiterator.offsetset.php
     * @param string $index <p>
     * The index to set for.
     * </p>
     * @param TValue $newval <p>
     * The new value to store at the index.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($index, $newval) { }
    /**
     * Unset value for an offset
     * @link http://php.net/manual/en/arrayiterator.offsetunset.php
     * @param string $index <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($index) { }
    /**
     * Append an element
     * @link http://php.net/manual/en/arrayiterator.append.php
     * @param TValue $value <p>
     * The value to append.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function append($value) { }
    /**
     * Get array copy
     * @link http://php.net/manual/en/arrayiterator.getarraycopy.php
     * @return array A copy of the array, or array of public properties
     * if ArrayIterator refers to an object.
     * @since 5.0.0
     */
    public function getArrayCopy() { }
    /**
     * Count elements
     * @link http://php.net/manual/en/arrayiterator.count.php
     * @return int The number of elements or public properties in the associated
     * array or object, respectively.
     * @since 5.0.0
     */
    public function count() { }
    /**
     * Get flags
     * @link http://php.net/manual/en/arrayiterator.getflags.php
     * @return string The current flags.
     * @since 5.1.0
     */
    public function getFlags() { }
    /**
     * Set behaviour flags
     * @link http://php.net/manual/en/arrayiterator.setflags.php
     * @param string $flags <p>
     * A bitmask as follows:
     * 0 = Properties of the object have their normal functionality
     * when accessed as list (var_dump, foreach, etc.).
     * 1 = Array indices can be accessed as properties in read/write.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function setFlags($flags) { }
    /**
     * Sort array by values
     * @link http://php.net/manual/en/arrayiterator.asort.php
     * @return void
     * @since 5.2.0
     */
    public function asort() { }
    /**
     * Sort array by keys
     * @link http://php.net/manual/en/arrayiterator.ksort.php
     * @return void
     * @since 5.2.0
     */
    public function ksort() { }
    /**
     * User defined sort
     * @link http://php.net/manual/en/arrayiterator.uasort.php
     * @param string $cmp_function <p>
     * The compare function used for the sort.
     * </p>
     * @return void
     * @since 5.2.0
     */
    public function uasort($cmp_function) { }
    /**
     * User defined sort
     * @link http://php.net/manual/en/arrayiterator.uksort.php
     * @param string $cmp_function <p>
     * The compare function used for the sort.
     * </p>
     * @return void
     * @since 5.2.0
     */
    public function uksort($cmp_function) { }
    /**
     * Sort an array naturally
     * @link http://php.net/manual/en/arrayiterator.natsort.php
     * @return void
     * @since 5.2.0
     */
    public function natsort() { }
    /**
     * Sort an array naturally, case insensitive
     * @link http://php.net/manual/en/arrayiterator.natcasesort.php
     * @return void
     * @since 5.2.0
     */
    public function natcasesort() { }
    /**
     * Unserialize
     * @link http://php.net/manual/en/arrayiterator.unserialize.php
     * @param string $serialized <p>
     * The serialized ArrayIterator object to be unserialized.
     * </p>
     * @return string The <b>ArrayIterator</b>.
     * @since 5.3.0
     */
    public function unserialize($serialized) { }
    /**
     * Serialize
     * @link http://php.net/manual/en/arrayiterator.serialize.php
     * @return string The serialized <b>ArrayIterator</b>.
     * @since 5.3.0
     */
    public function serialize() { }
    /**
     * Rewind array back to the start
     * @link http://php.net/manual/en/arrayiterator.rewind.php
     * @return void
     * @since 5.0.0
     */
    public function rewind() { }
    /**
     * Return current array entry
     * @link http://php.net/manual/en/arrayiterator.current.php
     * @return mixed The current array entry.
     * @since 5.0.0
     */
    public function current() { }
    /**
     * Return current array key
     * @link http://php.net/manual/en/arrayiterator.key.php
     * @return mixed The current array key.
     * @since 5.0.0
     */
    public function key() { }
    /**
     * Move to next entry
     * @link http://php.net/manual/en/arrayiterator.next.php
     * @return void
     * @since 5.0.0
     */
    public function next() { }
    /**
     * Check whether array contains more entries
     * @link http://php.net/manual/en/arrayiterator.valid.php
     * @return bool
     * @since 5.0.0
     */
    public function valid() { }
    /**
     * Seek to position
     * @link http://php.net/manual/en/arrayiterator.seek.php
     * @param int $position <p>
     * The position to seek to.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function seek($position) { }
}

/**
 * The DOMElement class
 * @link http://php.net/manual/en/class.domelement.php
 */
class DOMElement extends DOMNode  {
    /**
     * @return DOMNodeList<DOMElement>
     */
    public function getElementsByTagName ($name) {}
    /**
     * @return DOMNodeList<DOMElement>
     */
    public function getElementsByTagNameNS ($namespaceURI, $localName) {}
}

/**
 * @template TNode as DOMNode
 */
class DOMNodeList implements Traversable, Countable {
    /**
     * @var int
     * @since 5.0
     * The number of nodes in the list. The range of valid child node indices is 0 to length - 1 inclusive.
     * @link http://php.net/manual/en/class.domnodelist.php#domnodelist.props.length
     */
    public $length;

    /**
     * @return TNode|null
     */
    public function item ($index) {}
}
