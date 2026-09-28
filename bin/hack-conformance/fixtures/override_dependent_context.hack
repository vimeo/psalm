//// psalm-test: Psalm\Tests\PurityTemplateTest::overrideWithFixedCapabilitiesBeyondDependentParent
//// expect: error
//// note: a method whose context depends on a closure argument promises callers a call as
////       pure as the closures they pass; an override with a fixed context needing more
////       breaks that promise. Psalm compares the unconditional capabilities of the two
////       methods before their worst cases.

final class Box { public int $x = 0; }

abstract class Base {
  abstract public function run((function()[_]: void) $f)[ctx $f]: int;
}

final class Mutating extends Base {
  <<__Override>>
  public function run((function(): void) $f)[write_props]: int {
    $b = new Box();
    $b->x = 1;
    return $b->x;
  }
}
