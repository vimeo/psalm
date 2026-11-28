//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: a template without a lower bound may be bound to a type below Cat.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function generic<T>(Sink<T> $s): void {}
