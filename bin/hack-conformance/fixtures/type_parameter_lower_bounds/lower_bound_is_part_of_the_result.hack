//// expect: error
//// psalm-error: InvalidReturnStatement
//// note: mk(1) may return a Cat, so it is int|Cat, which is not an int.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function mk<T super Cat>(T $v): T { return new Cat(); }

function test(): int { return mk(1); }
