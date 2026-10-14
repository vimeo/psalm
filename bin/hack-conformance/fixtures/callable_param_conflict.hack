//// psalm-test: Psalm\Tests\Template\TypeVariableTest::constructorBoundThenClosureParamConflict
//// expect: error
//// note: a lower bound (int, via set) and an upper bound (Item, via the callable
////       parameter) cannot hold together (Hack: "Expected Item but got int").

final class Item { public int $id = 0; }

final class Box<T> {
  public function __construct() {}
  public function set(T $v): void {}
  public function each((function(T): mixed) $cb): void {}
}

function process(): void {
  $box = new Box();
  $box->set(5);
  $box->each((Item $item) ==> $item->id);
}
