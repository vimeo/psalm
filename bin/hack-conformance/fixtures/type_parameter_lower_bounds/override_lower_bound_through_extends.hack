//// expect: no-errors
//// note: the parent's lower bound T is Cat in a child extending P<Cat>, so the override may declare super Cat.
class Animal {}
class Cat extends Animal {}
abstract class P<T> {
  abstract public function m<TU super T>(TU $u): TU;
}
final class C extends P<Cat> {
  public function m<TU super Cat>(TU $u): TU { return $u; }
}
