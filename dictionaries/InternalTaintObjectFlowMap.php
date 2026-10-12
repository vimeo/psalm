<?php

declare(strict_types=1);

/**
 * The methods of the builtin classes that keep data given to them in the object they are called on, or give back
 * the data that object keeps. An entry also applies to the classes extending the class it names, unless they or a
 * closer parent have their own entry for the method. The object is followed as an array would be (the variable or
 * the property holding it), and an entry says:
 *
 * - `in`: the offset of each parameter whose taints the object keeps => the path it flows through: as an element
 *   (`arrayvalue-assignment`), as a key (`arraykey-assignment`), or as a whole (`=`, the parameter holds what it adds,
 *   e.g. another container);
 * - `out`: the path through which what the object keeps flows into the return value: one of its elements
 *   (`arrayvalue-fetch`), one of its keys (`arraykey-fetch`), or all of it (`=`);
 * - `key`: the offset of the parameter naming the element the method puts or takes: when given a literal, only that
 *   element's taints flow, as with `$array['key']`.
 *
 * What a builtin object is built from flows into it through the `@psalm-flow (...) -> return` of its constructor in
 * the stubs.
 *
 * @var array<lowercase-string, array{in?: non-empty-array<int, non-empty-string>, out?: non-empty-string, key?: int}>
 */
return [
    // ArrayAccess containers
    'arrayiterator::append' => ['in' => [0 => 'arrayvalue-assignment']],
    'arrayiterator::current' => ['out' => 'arrayvalue-fetch'],
    'arrayiterator::getarraycopy' => ['out' => '='],
    'arrayiterator::key' => ['out' => 'arraykey-fetch'],
    'arrayiterator::offsetget' => ['out' => 'arrayvalue-fetch', 'key' => 0],
    'arrayiterator::offsetset' => ['in' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment'], 'key' => 0],
    'arrayobject::append' => ['in' => [0 => 'arrayvalue-assignment']],
    'arrayobject::exchangearray' => ['in' => [0 => '='], 'out' => '='],
    'arrayobject::getarraycopy' => ['out' => '='],
    'arrayobject::getiterator' => ['out' => '='],
    'arrayobject::offsetget' => ['out' => 'arrayvalue-fetch', 'key' => 0],
    'arrayobject::offsetset' => ['in' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment'], 'key' => 0],
    'splfixedarray::current' => ['out' => 'arrayvalue-fetch'],
    'splfixedarray::offsetget' => ['out' => 'arrayvalue-fetch', 'key' => 0],
    'splfixedarray::offsetset' => ['in' => [1 => 'arrayvalue-assignment'], 'key' => 0],
    'splfixedarray::toarray' => ['out' => '='],
    'splobjectstorage::addall' => ['in' => [0 => '=']],
    'splobjectstorage::attach' => ['in' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment']],
    'splobjectstorage::current' => ['out' => 'arraykey-fetch'],
    'splobjectstorage::getinfo' => ['out' => 'arrayvalue-fetch'],
    'splobjectstorage::offsetget' => ['out' => 'arrayvalue-fetch'],
    'splobjectstorage::offsetset' => ['in' => [0 => 'arraykey-assignment', 1 => 'arrayvalue-assignment']],
    'splobjectstorage::setinfo' => ['in' => [0 => 'arrayvalue-assignment']],
    // lists, queues and heaps
    'spldoublylinkedlist::add' => ['in' => [1 => 'arrayvalue-assignment']],
    'spldoublylinkedlist::bottom' => ['out' => 'arrayvalue-fetch'],
    'spldoublylinkedlist::current' => ['out' => 'arrayvalue-fetch'],
    'spldoublylinkedlist::offsetget' => ['out' => 'arrayvalue-fetch', 'key' => 0],
    'spldoublylinkedlist::offsetset' => ['in' => [1 => 'arrayvalue-assignment'], 'key' => 0],
    'spldoublylinkedlist::pop' => ['out' => 'arrayvalue-fetch'],
    'spldoublylinkedlist::push' => ['in' => [0 => 'arrayvalue-assignment']],
    'spldoublylinkedlist::shift' => ['out' => 'arrayvalue-fetch'],
    'spldoublylinkedlist::top' => ['out' => 'arrayvalue-fetch'],
    'spldoublylinkedlist::unshift' => ['in' => [0 => 'arrayvalue-assignment']],
    'splqueue::dequeue' => ['out' => 'arrayvalue-fetch'],
    'splqueue::enqueue' => ['in' => [0 => 'arrayvalue-assignment']],
    'splheap::current' => ['out' => 'arrayvalue-fetch'],
    'splheap::extract' => ['out' => 'arrayvalue-fetch'],
    'splheap::insert' => ['in' => [0 => 'arrayvalue-assignment']],
    'splheap::top' => ['out' => 'arrayvalue-fetch'],
    'splpriorityqueue::current' => ['out' => 'arrayvalue-fetch'],
    'splpriorityqueue::extract' => ['out' => 'arrayvalue-fetch'],
    'splpriorityqueue::insert' => ['in' => [0 => 'arrayvalue-assignment']],
    'splpriorityqueue::top' => ['out' => 'arrayvalue-fetch'],
    // iterators over other iterators
    'appenditerator::append' => ['in' => [0 => '=']],
    'iteratoriterator::current' => ['out' => 'arrayvalue-fetch'],
    'iteratoriterator::getinneriterator' => ['out' => '='],
    'iteratoriterator::key' => ['out' => 'arraykey-fetch'],
    'recursiveiteratoriterator::current' => ['out' => 'arrayvalue-fetch'],
    'recursiveiteratoriterator::getinneriterator' => ['out' => '='],
    'recursiveiteratoriterator::key' => ['out' => 'arraykey-fetch'],
    // files: what is written can be read back
    'splfileobject::current' => ['out' => '='],
    'splfileobject::fgetcsv' => ['out' => '='],
    'splfileobject::fgets' => ['out' => '='],
    'splfileobject::fread' => ['out' => '='],
    'splfileobject::fwrite' => ['in' => [0 => '=']],
];
