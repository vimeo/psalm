//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: a template of a class without a lower bound may be bound to a type below Cat.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

final class Child<T> { public function f(Sink<T> $s): void {} }
