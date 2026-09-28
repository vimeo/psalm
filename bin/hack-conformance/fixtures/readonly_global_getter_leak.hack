//// psalm-test: Psalm\Tests\CapabilitiesTest::readGlobalsGetterResultCannotBeMutated
//// expect: error
//// note: a readonly value stays readonly through a function that returns it, so the
////       caller cannot mutate it either. Psalm flags the results of callees that may
////       read globals as reached from global state.

final class Box { public int $x = 0; public static ?Box $g = null; }

function get()[read_globals]: readonly ?Box { return readonly Box::$g; }

function leak()[read_globals, write_props]: void {
  $b = readonly get();
  if ($b is nonnull) { $b->x = 1; }
}
