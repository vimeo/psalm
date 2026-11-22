//// expect: no-errors
//// psalm-divergence: Psalm iterates the empty array of a `Coll<never>` as `mixed` and reports MixedAssignment
////       and MixedArgument for the loop body. Hack binds the loop variable to `nothing` and accepts the body,
////       dead code as it is.
//// note: iterating the items of a `Coll<nothing>`, which a bounded template
////       (`Bounded<nothing>`) gives as well, binds the loop variable to
////       `nothing`: the body typechecks, whatever it does with the item.

interface Named {}

final class Coll<+T> {
  public function all(): vec<T> { return vec[]; }
}

final class Bounded<+T as Named> {
  public function all(): vec<T> { return vec[]; }
}

function nameOf(Named $named): string { return ''; }

function names(): string {
  $result = '';
  foreach ((new Bounded())->all() as $item) {
    $result .= nameOf($item);
  }

  return $result;
}

function total(): int {
  $sum = 0;
  foreach ((new Coll())->all() as $item) {
    $sum += $item;
  }

  return $sum;
}
