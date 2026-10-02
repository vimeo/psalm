//// expect: error
//// psalm-error: InvalidTemplateParam
//// note: `super [write_props]` caps what a subclass may require: the upper bound of
////       Psalm's purity template, checked on `@extends`.

abstract class Doer {
  abstract const ctx C super [write_props];
  public function run()[this::C]: int { return 1; }
}

final class GlobalDoer extends Doer { const ctx C = [globals]; }
