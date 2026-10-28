//// psalm-test: Psalm\Tests\Template\TypeVariableTest::inhabitedVariableNotContainedByNeverReturn
//// expect: error
//// note: returning an inhabited variable where `nothing`/`never` is declared is
////       rejected (Hack: "Expected nothing ... invariant ... But got int"). Guards
////       the UnionTypeComparator never-arm fix against false negatives.

final class A<T> {
  public function __construct(private T $t) {}
  public function sortBy((function(T, T): mixed) $f): void {}
}

function f(): A<nothing> { return new A(5); }
