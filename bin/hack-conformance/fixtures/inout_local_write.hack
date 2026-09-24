//// psalm-test: Psalm\Tests\CapabilitiesTest::byReferenceArgumentsCostWhatTheyWrite
//// expect: no-errors
//// note: writing an `inout` parameter is not an effect, and passing a local `inout` to
////       such a function from a `[]` context is fine: the write lands in the caller's own
////       local. Psalm charges a by-reference argument for what it is, nothing for a local.

function set_ref(inout int $x)[]: void { $x = 1; }

function passes_local()[]: int {
  $x = 0;
  set_ref(inout $x);
  return $x;
}

function passes_own_inout(inout int $x)[]: void { set_ref(inout $x); }
