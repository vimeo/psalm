//// expect: no-errors
//// hhconfig: union_intersection_type_hints = true
//// psalm-ignore: InvalidTemplateParam
//// note: two constructions of a covariant class returned through a union
////       return type. Hack localizes the declared `(It<string> | It<int>)` to
////       `It<(string | int)>` (Typing_union.union_list merges same-class
////       covariant members), so each construction's variable just gains the
////       upper bound `string | int`; the ternary itself unions to It<(int | string)>.
////       Psalm reports the covariant TKey in `sortBy`'s closure parameter (a covariant
////       position, which Hack accepts) unless the method is mutation-free, hence the
////       ignored InvalidTemplateParam.

final class It<+TKey as arraykey> {
  public function __construct(dict<TKey, mixed> $array = dict[]) {}
  public function sortBy((function(TKey): mixed) $func): void {}
}

function takeNew(): (It<string> | It<int>) {
  return \mt_rand(0, 1) === 0 ? new It(dict[0 => 1]) : new It(dict["hello" => 1]);
}
