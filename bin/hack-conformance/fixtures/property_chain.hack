//// psalm-test: Psalm\Tests\Template\TypeVariableTest::propertyFetchThenMethodCallOnElement
//// expect: no-errors
//// note: method call reached through a property fetch of a type-variable object.

final class User { public function getId(): int { return 0; } }

final class Box<T> {
  public function __construct(public T $value) {}
  public function sortBy((function(T, T): mixed) $func): void {}
}

function pchain(): void {
  $b = new Box(new User());
  $b->value->getId();
}
