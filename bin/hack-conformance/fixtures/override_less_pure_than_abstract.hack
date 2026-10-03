//// psalm-test: Psalm\Tests\CapabilitiesTest::overrideOfAbstractMayNotAddWriteProps
//// expect: error
//// note: an override may not require a capability its abstract parent does not grant:
////       callers through the parent type only provide the parent's context. Psalm
////       reports ImmutableDependency, Hack reports Typing[4341].

final class Box { public int $x = 0; }

abstract class P {
  abstract public function m(Box $b)[]: int;
}

final class C extends P {
  <<__Override>>
  public function m(Box $b)[write_props]: int {
    $b->x = 1;
    return $b->x;
  }
}
