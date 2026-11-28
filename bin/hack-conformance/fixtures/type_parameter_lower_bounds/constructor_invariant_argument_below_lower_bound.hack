//// expect: error
//// psalm-error: InvalidArgument
//// note: Box<int> pins T of the new Holder to int, which does not contain the lower bound Cat.
class Animal {}
class Cat extends Animal {}
final class Box<T> {
  public function __construct(public T $v) {}
}
final class Holder<T super Cat> {
  public function __construct(public Box<T> $b) {}
}
function test(Box<int> $b): void {
  new Holder($b);
}
