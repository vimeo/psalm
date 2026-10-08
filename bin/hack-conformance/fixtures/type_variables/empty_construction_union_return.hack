//// expect: no-errors
//// hhconfig: union_intersection_type_hints = true
//// psalm-ignore: InvalidTemplateParam
//// note: an empty `new It()` returned where the declared type is a union of two
////       key instantiations of a covariant class. Hack localizes the declared
////       `(It<string> | It<int>)` to `It<(string | int)>` (Typing_union.union_list
////       merges same-class covariant members), so the variable just gains the
////       upper bound `string | int`.
////       Psalm reports the covariant TKey in `sortBy`'s closure parameter (a covariant
////       position, which Hack accepts) unless the method is mutation-free, hence the
////       ignored InvalidTemplateParam.

final class It<+TKey as arraykey> {
  public function __construct(dict<TKey, mixed> $array = dict[]) {}
  public function sortBy((function(TKey): mixed) $func): void {}
}

function takeNew(): (It<string> | It<int>) {
  return new It();
}
