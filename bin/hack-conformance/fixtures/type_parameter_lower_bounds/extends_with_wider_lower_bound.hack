//// expect: no-errors
//// note: Cat <: Animal <: TU, so TU is above the lower bound Cat of the parent.
class Animal {}
class Cat extends Animal {}
class Dog extends Animal {}
class Sink<T super Cat> {
  public function put(T $v): void {}
}

class Child<TU super Animal> extends Sink<TU> {}
