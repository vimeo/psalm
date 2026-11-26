//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: int is not a supertype of the lower bound Cat.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function takesIntSink(Sink<int> $s): void {}
