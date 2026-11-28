//// expect: error
//// psalm-error: InvalidReturnStatement
//// note: a Cat fits T, but a Box<Cat> is not a Box<T>: Box is invariant.
class Animal {}
class Cat extends Animal {}
final class Box<T> {
  public function __construct(public T $v) {}
}
final class Lower<T super Cat> {
  public function conv(Box<Cat> $c): Box<T> { return $c; }
}
