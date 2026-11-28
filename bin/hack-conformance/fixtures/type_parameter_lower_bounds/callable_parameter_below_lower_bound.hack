//// expect: error
//// psalm-error: InvalidArgument
//// note: a function of int would be called with T, which may be Cat.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function each<T super Cat>((function(T): void) $f): void {}

function test(): void { each((int $i) ==> {}); }
