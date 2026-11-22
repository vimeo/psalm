//// expect: no-errors
//// psalm-divergence: Psalm infers `Coll<mixed>` for an invariant template nothing can constrain, so it
////       reports MixedReturnTypeCoercion where Hack fixes the type variable to `int` through the declared
////       return type.
//// note: an invariant type parameter nothing can constrain is a type variable
////       (`Coll<_>`) that the expected type fixes: the construction fits any
////       `Coll<X>`.

final class Coll<T> {
  public function all(): vec<T> { return vec[]; }
}

function takesInts(Coll<int> $c): void {}

function pass(): void {
  takesInts(new Coll());
}

function ints(): Coll<int> {
  return new Coll();
}
