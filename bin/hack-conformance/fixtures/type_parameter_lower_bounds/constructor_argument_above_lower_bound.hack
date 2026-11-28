//// expect: no-errors
//// note: new Holder(new Animal()) is a Holder<Animal>.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}
final class Holder<T super Cat> {
  public function __construct(public T $v) {}
}

function test(): Animal { return (new Holder(new Animal()))->v; }
