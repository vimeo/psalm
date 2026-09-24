//// psalm-test: Psalm\Tests\PurityTemplateTest::classPurityTemplateBoundRejectsWiderExtends
//// expect: error
//// note: `super [write_props]` caps what a subclass may require: Psalm's
////       `@psalm-purity-template C of write-props` is the same upper bound, checked on
////       `@extends`.

abstract class Doer {
  abstract const ctx C super [write_props];
  public function run()[this::C]: int { return 1; }
}

final class GlobalDoer extends Doer { const ctx C = [globals]; }
