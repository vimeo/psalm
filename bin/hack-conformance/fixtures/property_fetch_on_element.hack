//// psalm-test: Psalm\Tests\Template\TypeVariableTest::propertyFetchOnTypeVariableIteratorElement
//// expect: no-errors
//// note: an element read out of a type-variable `vec<TValue>` is usable as a
////       property-fetch receiver through the construction's inferred bound.

final class User { public int $id = 0; }

final class XIter<TValue> {
  public function __construct(private vec<TValue> $array = vec[]) {}
  public function sortBy((function(TValue, TValue): mixed) $func): void {}
  public function toArray(): vec<TValue> { return $this->array; }
}

function run(vec<User> $users): void {
  $items = (new XIter($users))->toArray();
  foreach ($items as $user) {
    echo $user->id;
  }
}
