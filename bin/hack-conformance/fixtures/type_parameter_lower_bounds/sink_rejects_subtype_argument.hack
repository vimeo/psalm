//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: Kitten is below the lower bound Cat, so it is no type argument of Sink.
class Animal {}
class Cat extends Animal {}
class Kitten extends Cat {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function takesKittenSink(Sink<Kitten> $s): void {}
