//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: the lower bound of a method template is a covariant position, where a contravariant class template may not be.
class Animal {}
class Cat extends Animal {}
interface Consumer<-T> {
  public function m<TU super T>(TU $u): TU;
}
