//// psalm-test: Psalm\Tests\Template\TypeVariableTest::nestedConstructionElementArithmetic
//// expect: no-errors
//// note: nested construction; inner element resolves to int and supports arithmetic.

final class Box<T> {
  public function __construct(public T $value) {}
  public function sortBy((function(T, T): mixed) $func): void {}
}

function nested(): void {
  $bb = new Box(new Box(5));
  $inner = $bb->value;
  $x = $inner->value + 1;
}
