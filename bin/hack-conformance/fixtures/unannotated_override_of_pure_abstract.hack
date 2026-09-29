//// psalm-test: Psalm\Tests\CapabilitiesTest::unannotatedOverrideOfPureAbstractIsImpure
//// expect: error
//// note: an override without a context has the default one (Hack's `[defaults]`, Psalm's
////       impure), however pure its body is: neither checker infers an override's
////       capabilities from its body, so it does not match a pure abstract parent.

abstract class P {
  abstract public function m()[]: int;
}

final class C extends P {
  <<__Override>>
  public function m(): int { return 1; }
}
