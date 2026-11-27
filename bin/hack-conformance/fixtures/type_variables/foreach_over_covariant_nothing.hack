//// expect: no-errors
//// note: a loop over the `vec<TValue>` of a covariant `new` that nothing constrains
////       (`TValue` is `nothing`) accepts any use of its item.

final class Bounded<+TValue as Countable> {
  public function all(): vec<TValue> { return vec[]; }
}

function run(): void {
  foreach ((new Bounded())->all() as $item) {
    echo count($item);
  }
}
