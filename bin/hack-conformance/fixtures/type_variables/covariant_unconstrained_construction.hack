//// expect: no-errors
//// note: `new` of a class whose covariant type parameter nothing can constrain
////       (no method takes it) is a `Coll<nothing>`, a subtype of every
////       instantiation, so it fits an argument and a return type of any
////       `Coll<X>`. A bound on the parameter doesn't change that
////       (`Bounded<nothing>`).

interface Named {}

final class Coll<+T> {
  public function all(): vec<T> { return vec[]; }
}

final class Bounded<+T as Named> {
  public function all(): vec<T> { return vec[]; }
}

function takesInts(Coll<int> $c): void {}

function pass(): void {
  takesInts(new Coll());
}

function ints(): Coll<int> {
  return new Coll();
}

function named(): Bounded<Named> {
  return new Bounded();
}
