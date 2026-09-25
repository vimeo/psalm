//// psalm-test: Psalm\Tests\CapabilitiesTest::throwingExceptionWithUnannotatedImpureConstructor
//// expect: error
//// note: `throw new E()` runs E's constructor like any `new`; there is no exemption for
////       exceptions. Psalm no longer trusts unannotated exception constructors either.

final class MyException extends Exception {
  public function __construct() { echo "created"; parent::__construct("x"); }
}

function fail()[]: int { throw new MyException(); }
