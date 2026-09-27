//// psalm-test: Psalm\Tests\Template\TypeVariableTest::typedClosureParamAgainstEmptyConstruction
//// expect: no-errors
//// note: an empty construction assigned to an `ArrayCollection<int, DateTime>`
////       property, then handed a lambda whose param is typed DateTime. The typed
////       lambda param constrains the variable from above; the empty `dict[]`
////       only gives it the lower bound `nothing`. (The original form of
////       ClassTemplateTest::allowPropertyCoercion before PR #11972 changed it.)

final class ArrayCollection<TKey as arraykey, T> {
  public function __construct(private dict<TKey, T> $elements = dict[]) {}
  public function filter((function(T): bool) $p): ArrayCollection<TKey, T> { return $this; }
}

final class Test {
  private ArrayCollection<int, DateTime> $c;
  public function __construct() {
    $this->c = new ArrayCollection();
    $this->c->filter((DateTime $dt): bool ==> $dt === $dt);
  }
}
