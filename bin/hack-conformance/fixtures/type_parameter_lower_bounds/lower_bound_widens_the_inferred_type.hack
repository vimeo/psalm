//// expect: no-errors
//// note: the lower bound is one more lower bound of the template: keep(1) is int|Cat, not an error.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function keep<T super Cat>(T $v): T { return $v; }

function test(): void {
  keep(1);
  keep(new Animal());
  keep(new Cat());
}
