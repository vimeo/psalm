//// expect: error
//// psalm-error: MethodSignatureMismatch
//// note: an override may not add a lower bound to a method template: m<int> is no longer allowed.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}
class Base {
  public function m<TU>(TU $u): TU { return $u; }
}

final class Child extends Base {
  public function m<TU super Cat>(TU $u): TU { return $u; }
}
