//// expect: no-errors
//// note: the lower bound itself is a type argument of Sink.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function takesCatSink(Sink<Cat> $s): void { $s->put(new Cat()); }
