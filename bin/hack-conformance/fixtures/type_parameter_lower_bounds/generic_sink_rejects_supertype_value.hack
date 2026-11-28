//// expect: error
//// psalm-error: InvalidArgument
//// note: T may be Cat, so an Animal does not fit Sink<T>.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function generic<T super Cat>(Sink<T> $s): void { $s->put(new Animal()); }
