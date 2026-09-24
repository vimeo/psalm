//// psalm-test: Psalm\Tests\PurityTemplateTest::classPurityTemplateLowerBound
//// expect: no-errors
//// note: with `as [write_props]`, a method declared `[this::C]` may use write_props in its
////       body whatever the subclass binds C to. Psalm desugars the lower bound into fixed
////       capabilities of the methods depending on the template.

function do_write_props()[write_props]: void {}

abstract class Doer {
  abstract const ctx C as [write_props];
  public function run()[this::C]: void { do_write_props(); }
}

final class IoDoer extends Doer { const ctx C = [write_props, defaults]; }

function use_io(IoDoer $d)[defaults]: void { $d->run(); }
