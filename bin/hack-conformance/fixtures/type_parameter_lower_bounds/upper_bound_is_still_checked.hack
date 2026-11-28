//// expect: error
//// psalm-error: InvalidArgument
//// note: with both bounds, the upper bound rejects int.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function keep2<T as Animal super Cat>(T $v): T { return $v; }

function test(): void { keep2(1); }
