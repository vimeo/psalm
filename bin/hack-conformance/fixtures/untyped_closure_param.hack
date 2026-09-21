//// psalm-test: Psalm\Tests\Template\TypeVariableTest::untypedClosureParamResolvesInferredObjectElement
//// expect: no-errors
//// note: an untyped lambda parameter takes the construction's inferred element
////       type (Item), so the property fetch inside the lambda body resolves.

final class Coll<TValue> {
  public function __construct(Traversable<TValue> $data) {}
  public function each((function(TValue): string) $cb): void {}
}

final class Item {
  public int $id = 0;
}

function process(): void {
  $c = new Coll(vec[new Item()]);
  $c->each($item ==> (string)$item->id);
}
