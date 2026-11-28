//// expect: error
//// psalm-error: InvalidReturnStatement
//// note: new Holder(1) is a Holder<int|Cat>: its value is not an int.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}
final class Holder<T super Cat> {
  public function __construct(public T $v) {}
}

function test(): int { return (new Holder(1))->v; }
