//// psalm-test: Psalm\Tests\CapabilitiesTest::readGlobalsCannotMutateGlobalObject
//// expect: error
//// note: `read_globals` only hands out `readonly` views of static properties, so a
////       function with read_globals and write_props still cannot mutate an object it
////       reached through a static property. Psalm tracks the same thing with the
////       from_global_state flag: mutating such a value needs write-globals.

final class Box { public int $x = 0; public static ?Box $g = null; }

function leak()[read_globals, write_props]: void {
  $b = readonly Box::$g;
  if ($b is nonnull) { $b->x = 1; }
}
