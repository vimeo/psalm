//// psalm-test: Psalm\Tests\Template\TypeVariableTest::emptyConstructionAgainstUnionKeyReturn
//// expect: no-errors
//// hhconfig: union_intersection_type_hints = true
//// note: an empty `new It()` returned where the declared type is a union of two
////       key instantiations of a covariant class. Hack localizes the declared
////       `(It<string> | It<int>)` to `It<(string | int)>` (Typing_union.union_list
////       merges same-class covariant members), so the variable just gains the
////       upper bound `string | int`.

final class It<+TKey as arraykey> {
  public function __construct(dict<TKey, mixed> $array = dict[]) {}
  public function sortBy((function(TKey): mixed) $func): void {}
}

function takeNew(): (It<string> | It<int>) {
  return new It();
}
