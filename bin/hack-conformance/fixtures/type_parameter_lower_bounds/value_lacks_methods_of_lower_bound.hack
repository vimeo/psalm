//// expect: error
//// psalm-error: PossiblyUndefinedMethod
//// note: with both bounds, a value of the template reads as the upper bound, which has no meow().
class Animal { public function eat(): void {} }
class Cat extends Animal { public function meow(): void {} }
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function f<T as Animal super Cat>(T $v): void { $v->meow(); }
