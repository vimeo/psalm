//// psalm-test: Psalm\Tests\Template\TypeVariableTest::arrayAccess
//// expect: no-errors
//// note: nested array access on an element read out of a type-variable list.

final class XIteratorOnArray<TTValue> {
  public function __construct(private vec<TTValue> $array = vec[]) {}
  public function sortBy((function(TTValue, TTValue): mixed) $func): void {}
  public function toArray(): vec<TTValue> { return $this->array; }
}

function match2(): void {
  $r = (new XIteratorOnArray(vec[vec[1, 2]]))->toArray();
  echo (string)($r[0][0]);
}
