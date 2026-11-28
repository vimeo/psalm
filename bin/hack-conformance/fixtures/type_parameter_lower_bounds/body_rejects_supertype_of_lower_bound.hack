//// expect: error
//// psalm-error: InvalidArgument
//// note: inside the class, T may be Cat, so an Animal does not fit it.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
  public function fill(): void { $this->put(new Animal()); }
}
