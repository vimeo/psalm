//// psalm-test: Psalm\Tests\Template\TypeVariableTest::methodCallOnIteratorElement
//// expect: no-errors
//// note: method call on a foreach element of a type-variable list.

final class User {
  public function getId(): ?int { return null; }
}

final class XIteratorOnArray<TTValue> {
  public function __construct(private vec<TTValue> $array = vec[]) {}
  public function sortBy((function(TTValue, TTValue): mixed) $func): void {}
  public function toArray(): vec<TTValue> { return $this->array; }
}

function prepareData(vec<User> $users): void {
  $users = (new XIteratorOnArray($users))->toArray();
  foreach ($users as $user) {
    $user->getId();
  }
}
