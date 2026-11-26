//// expect: no-errors
//// note: a type argument of a template with a lower bound may be the lower bound or any supertype of it, and a
////       value of the lower bound fits the template, also where the argument is another template with that bound.
class Animal {}
class Cat extends Animal {}
final class Sink<T super Cat> {
  public function put(T $v): void {}
}

function takesAnimalSink(Sink<Animal> $s): void { $s->put(new Cat()); }
function takesMixedSink(Sink<mixed> $s): void { $s->put(new Cat()); }
function generic<T super Cat>(Sink<T> $s): void { $s->put(new Cat()); }
