//// psalm-test: Psalm\Tests\CapabilitiesTest::unannotatedImplementationOfPureInterfaceIsImpure
//// expect: error
//// note: Psalm's interface-level `@psalm-pure` applies to every method; Hack has no
////       class-level contexts, so each interface method carries `[]`. An implementation
////       without a context has the default one and does not match.

interface I {
  public function m()[]: int;
}

final class D implements I {
  public function m(): int { return 1; }
}
