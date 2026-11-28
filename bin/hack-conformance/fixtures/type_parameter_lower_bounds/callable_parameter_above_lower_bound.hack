//// expect: no-errors
//// note: a function of Animal may be called with T bound to Animal.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function each<T super Cat>((function(T): void) $f): void {}

function test(): void { each((Animal $a) ==> {}); }
