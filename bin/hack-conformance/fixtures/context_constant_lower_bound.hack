//// psalm-test: Psalm\Tests\PurityTemplateTest::classPurityTemplateLowerBoundRejectsSmallerExtends
//// expect: error
//// note: `as [write_props]` makes every subclass require at least write_props, so a
////       subclass binding the constant to `[]` is rejected. Psalm's
////       `@psalm-purity-template C super write-props` is the same lower bound, checked on
////       `@extends`.

function do_write_props()[write_props]: void {}

abstract class Doer {
  abstract const ctx C as [write_props];
  public function run()[this::C]: void { do_write_props(); }
}

final class PureDoer extends Doer { const ctx C = []; }
