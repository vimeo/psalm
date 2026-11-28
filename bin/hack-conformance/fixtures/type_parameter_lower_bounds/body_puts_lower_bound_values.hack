//// expect: no-errors
//// note: inside the class, a value of the lower bound or below it fits the template.
class Animal {}
class Cat extends Animal {}
class Kitten extends Cat {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
  public function fill(): void {
    $this->put(new Cat());
    $this->put(new Kitten());
  }
}
