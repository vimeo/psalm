//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: Dog <: TU says nothing about Cat <: TU.
class Animal {}
class Cat extends Animal {}
class Dog extends Animal {}
class Sink<T super Cat> {
  public function put(T $v): void {}
}

class Child<TU super Dog> extends Sink<TU> {}
