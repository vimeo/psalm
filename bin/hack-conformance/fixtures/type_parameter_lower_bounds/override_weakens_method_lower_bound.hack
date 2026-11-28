//// expect: no-errors
//// note: an override may lower the lower bound of a method template: it accepts every type argument the parent does.
class Animal {}
class Cat extends Animal {}
class Kitten extends Cat {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}
class Base {
  public function m<TU super Cat>(TU $u): TU { return $u; }
}

final class Child extends Base {
  public function m<TU super Kitten>(TU $u): TU { return $u; }
}
