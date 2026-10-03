//// psalm-test: Psalm\Tests\CapabilitiesTest::overrideOfAbstractMayRequireFewerCapabilities
//// expect: no-errors
//// note: an override may require fewer capabilities than the abstract or interface method
////       it implements, whether the parent names them or leaves the default (Hack's
////       `[defaults]`, Psalm's `@psalm-impure`). Psalm's `read-props` is Hack's `[]`: Hack
////       always allows reading properties.

final class Box { public int $x = 0; }

abstract class P {
  abstract public function m(Box $b)[write_props]: int;
  abstract public function n(): int;
}

final class C extends P {
  <<__Override>>
  public function m(Box $b)[]: int { return $b->x; }

  <<__Override>>
  public function n()[]: int { return 1; }
}

interface I {
  public function m(): int;
}

final class D implements I {
  public function m()[]: int { return 1; }
}
