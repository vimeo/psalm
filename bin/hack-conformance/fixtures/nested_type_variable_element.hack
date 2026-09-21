//// psalm-test: Psalm\Tests\Template\TypeVariableTest::arrayAccessOnNestedTypeVariableElement
//// expect: no-errors
//// note: a construction fed from a container whose element type is itself a
////       type variable resolves through the whole chain to the concrete shape.

final class XIter<TValue> {
  public function __construct(private Container<TValue> $array = vec[]) {}
  public function sortBy((function(TValue, TValue): mixed) $func): void {}
  public function toAssoc(): dict<string, TValue> { throw new \Exception("stub"); }
  public function toList(): vec<TValue> { return vec($this->array); }
}

function run(vec<shape('id' => int)> $rows): void {
  $assoc = (new XIter($rows))->toAssoc();
  $list = (new XIter($assoc))->toList();
  foreach ($list as $row) {
    echo $row['id'];
  }
}
