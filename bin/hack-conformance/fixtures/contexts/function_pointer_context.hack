//// expect: error
//// psalm-error: ImpureFunctionCall
//// note: a function pointer carries the context of the function it points to, so
////       calling it needs the same capabilities as calling the function. Psalm's
////       first-class callables carry their capabilities the same way.

function roll(): int { return 4; }

function pure_roll()[]: int {
  $r = roll<>;
  return $r();
}
