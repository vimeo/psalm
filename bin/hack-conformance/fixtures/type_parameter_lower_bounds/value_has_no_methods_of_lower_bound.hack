//// expect: error
//// psalm-error: MixedMethodCall
//// note: a value of a template with only a lower bound is any type: it has no method of the lower bound.
class Animal {}
class Cat extends Animal { public function meow(): void {} }
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function f<T super Cat>(T $v): void { $v->meow(); }
