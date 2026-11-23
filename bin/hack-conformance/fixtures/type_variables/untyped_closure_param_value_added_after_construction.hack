//// expect: no-errors
//// note: a value added after the construction ("x") is one the untyped lambda
////       parameter receives too, so folding the list neither conflicts with the
////       construction's inferred element type (int) nor drops the added one.

final class Coll<T> {
  public function __construct(private vec<T> $items) {}
  public function add(T $item): void { $this->items[] = $item; }
  public function all(): vec<T> { return $this->items; }
}

function reduce<Ta, Tb>(vec<Ta> $items, (function(Tb, Ta): Tb) $f, Tb $init): Tb {
  return $init;
}

function process(): void {
  $c = new Coll(vec[1]);
  $c->add("x");
  $last = reduce($c->all(), ($carry, $i) ==> $i, null);
}
