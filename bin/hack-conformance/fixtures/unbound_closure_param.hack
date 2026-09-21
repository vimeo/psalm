//// psalm-test: Psalm\Tests\Template\TypeVariableTest::unboundTemplateSolvesToClosureParam
//// expect: no-errors
//// note: an unbound variable gets only an upper bound from the callable parameter,
////       which is satisfiable, so no error (Hack solves the variable).

final class Item { public int $id = 0; }

final class Box<T> {
  public function __construct() {}
  public function each((function(T): mixed) $cb): void {}
}

function process(): void {
  $box = new Box();
  $box->each((Item $item) ==> $item->id);
}
