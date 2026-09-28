//// psalm-test: Psalm\Tests\Template\TypeVariableTest::castTypeVariableToString
//// expect: no-errors
//// note: an element read out of a type-variable `vec<TValue>` can be cast to
////       string through the construction's inferred bound (int).

final class XIter<TValue> {
  public function __construct(private vec<TValue> $array) {}
  public function sortBy((function(TValue, TValue): mixed) $func): void {}
  public function toArray(): vec<TValue> { return $this->array; }
}

function run(): void {
  foreach ((new XIter(vec[1]))->toArray() as $v) {
    echo (string)$v;
  }
}
