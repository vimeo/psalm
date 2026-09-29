//// psalm-test: Psalm\Tests\CapabilitiesTest::unannotatedAbstractMethodsMayBeImplementedImpurely
//// expect: no-errors
//// note: an abstract or interface method without a context has the default one, so any
////       implementation fits. Psalm also reports MissingAbstractPureAnnotation and
////       MissingInterfaceImmutableAnnotation here (suppressed in the Psalm test) to push
////       for explicit annotations for security analysis; Hack has no counterpart, in
////       neither the typechecker nor `hh_client --lint`.

abstract class P {
  abstract public function m(): int;
}

final class C extends P {
  <<__Override>>
  public function m(): int {
    echo "x";
    return 1;
  }
}

interface I {
  public function m(): int;
}

final class D implements I {
  public function m(): int {
    echo "x";
    return 1;
  }
}
