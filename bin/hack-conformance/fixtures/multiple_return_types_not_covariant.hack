//// psalm-test: Psalm\Tests\Template\TypeVariableTest::multipleReturnTypesNotCovariant
//// expect: no-errors
//// hhconfig: union_intersection_type_hints = true
//// note: same as multiple_return_types.hack but with an invariant key, so Hack
////       cannot merge the declared `(It<string> | It<int>)` into one class type;
////       the ternary stays `(It<int> | It<string>)` and each arm reconciles
////       against its own member of the union.
////       The value goes through a local on purpose: written as a direct
////       `return cond ? new It(...) : new It(...)`, Hack pushes the expected
////       return type into `new` and instantiates the invariant TKey from the
////       FIRST union member (It<string>), so `dict[0 => 1]` is rejected ("must
////       match exactly (it is invariant)") — even a lone
////       `return new It(dict[0 => 1]);` fails that way. That is an artifact of
////       Hack's expected-type propagation, not of its constraint solving, and
////       Psalm does not propagate return types into constructions.

final class It<TKey as arraykey> {
  public function __construct(dict<TKey, mixed> $array = dict[]) {}
  public function sortBy((function(TKey): mixed) $func): void {}
}

function takeNew(): (It<string> | It<int>) {
  $x = \mt_rand(0, 1) === 0 ? new It(dict[0 => 1]) : new It(dict["hello" => 1]);
  return $x;
}
