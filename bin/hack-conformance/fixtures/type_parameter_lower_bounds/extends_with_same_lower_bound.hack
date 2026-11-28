//// expect: no-errors
//// note: a template with the same lower bound may be the type argument of the parent.
class Animal {}
class Cat extends Animal {}
class Dog extends Animal {}
class Sink<T super Cat> {
  public function put(T $v): void {}
}

class Child<TU super Cat> extends Sink<TU> {}
