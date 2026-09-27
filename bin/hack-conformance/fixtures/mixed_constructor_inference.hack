//// psalm-test: Psalm\Tests\Template\TypeVariableTest::mixedConstructorInferenceCoercesClosureParam
//// expect: error
//// note: the constructor infers TValue from a mixed-valued container, and the
////       typed lambda parameter then asks for Item: mixed is not Item
////       (Hack: "Expected Item ... But got mixed").

final class Item {
  public int $id = 0;
}

final class Table<TValue> {
  public function __construct(KeyedTraversable<arraykey, TValue> $data) {}
  public function column((function(TValue): mixed) $content): void {}
}

function prepareTable(KeyedTraversable<arraykey, mixed> $items): void {
  $table = new Table($items);
  $table->column((Item $item): int ==> $item->id);
}
