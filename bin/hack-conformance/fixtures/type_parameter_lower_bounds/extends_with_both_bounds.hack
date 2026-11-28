//// expect: no-errors
//// note: the lower bound Cat of TU meets the lower bound of the parent; the upper bound is beside the point.
class Animal {}
class Cat extends Animal {}
class Dog extends Animal {}
class Sink<T super Cat> {
  public function put(T $v): void {}
}

class Child<TU as Animal super Cat> extends Sink<TU> {}
