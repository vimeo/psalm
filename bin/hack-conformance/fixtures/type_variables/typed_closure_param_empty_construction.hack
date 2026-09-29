//// expect: no-errors
//// psalm-divergence: Psalm types the empty construction ArrayCollection<never, never>
////       and keeps that type after the assignment to the property, so the lambda
////       parameter is checked against never (ParadoxicalCondition) instead of the
////       variable taking DateTime as its upper bound.
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
