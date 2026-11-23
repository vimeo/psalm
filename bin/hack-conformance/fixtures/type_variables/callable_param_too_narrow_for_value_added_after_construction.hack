//// expect: error
//// psalm-error: IncompatibleTypeParameters
//// note: the callable parameter also receives the value added after the
////       construction ("x"), so a lambda that only takes int is rejected
////       (Hack: "Expected int but got string").

final class Coll<T> {
  public function __construct(private vec<T> $items) {}
  public function add(T $item): void { $this->items[] = $item; }
  public function each((function(T): void) $cb): void {}
}

function process(): void {
  $c = new Coll(vec[1]);
  $c->add("x");
  $c->each((int $i) ==> {});
}
