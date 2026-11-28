//// expect: no-errors
//// note: Box<Animal> pins T to Animal, which contains the lower bound Cat.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}
final class Box<T> {
  public function __construct(public T $v) {}
  public function set(T $v): void {}
}

function fill<T super Cat>(Box<T> $b): void { $b->set(new Cat()); }

function callFill(Box<Animal> $b): void { fill($b); }
