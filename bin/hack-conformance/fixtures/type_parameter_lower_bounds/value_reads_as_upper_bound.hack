//// expect: error
//// psalm-error: MixedReturnTypeCoercion
//// note: a value of a template with only a lower bound is any type, not the lower bound.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function f<T super Cat>(T $v): Cat { return $v; }
