//// expect: no-errors
//// note: a loop over a `vec<nothing>` accepts any use of its item: nothing is a
////       subtype of every type, so any use of the item is accepted.

final class User { public int $id = 0; }

function nothing(): vec<nothing> { return vec[]; }

function run(): void {
  foreach (nothing() as $user) {
    echo $user->id;
  }
}
