//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: a template without a lower bound may be bound to a type below Cat.
class Animal {}
class Cat extends Animal {}
class Dog extends Animal {}
class Sink<T super Cat> {
  public function put(T $v): void {}
}

class Child<TU> extends Sink<TU> {}
