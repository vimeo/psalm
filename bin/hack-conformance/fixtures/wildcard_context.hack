//// psalm-test: Psalm\Tests\PurityTemplateTest::wildcardPurity
//// expect: no-errors
//// note: `(function()[_]: int) $f` with `[ctx $f]` makes the function as pure as the
////       closure it is given, without naming a context: Psalm's `Closure<_>(): int $f`
////       parameter does the same without declaring a purity template.

function apply((function()[_]: int) $f)[ctx $f]: int { return $f(); }

final class Runner {
  public function run((function()[_]: int) $f)[ctx $f]: int { return $f(); }
}

function use_pure(Runner $r)[]: int { return apply(()[] ==> 1) + $r->run(()[] ==> 3); }

function use_io()[defaults]: int { return apply(() ==> { echo "x"; return 1; }); }
