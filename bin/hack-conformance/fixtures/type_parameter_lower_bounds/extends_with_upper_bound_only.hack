//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: an upper bound Cat does not make TU a supertype of Cat.
class Animal {}
class Cat extends Animal {}
class Dog extends Animal {}
class Sink<T super Cat> {
  public function put(T $v): void {}
}

class Child<TU as Cat> extends Sink<TU> {}
