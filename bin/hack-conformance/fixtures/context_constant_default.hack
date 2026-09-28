//// psalm-test: Psalm\Tests\PurityTemplateTest::classPurityTemplateDefault
//// expect: no-errors
//// note: an abstract context constant with a default is synthesized for the first
////       concrete class that does not define it: Psalm's `@psalm-purity-template C of
////       write-props = pure` binds C for subclasses without an `@extends` argument.

abstract class Doer {
  abstract const ctx C super [write_props] = [];
  public function run()[this::C]: int { return 1; }
}

final class DefaultDoer extends Doer {}

final class MutatingDoer extends Doer { const ctx C = [write_props]; }

function useDefault(DefaultDoer $d)[]: int { return $d->run(); }

function useMutating(MutatingDoer $d)[write_props]: int { return $d->run(); }
