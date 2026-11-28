//// expect: error
//// psalm-error: UndefinedMethod
//// note: an inherited constructor's argument is part of the type of the template it binds: new Child(new Animal()) holds an Animal.
class Animal {}
class Cat extends Animal {
  public function meow(): void {}
}
class Holder<T> {
  public function __construct(public T $v) {}
}
final class Child<TU super Cat> extends Holder<TU> {}
function test(): void {
  $c = new Child(new Animal());
  $c->v->meow();
}
