//// psalm-test: Psalm\Tests\CapabilitiesTest::capabilitiesAlias
//// expect: no-errors
//// note: a class may name a context once (`const ctx Storage = [...]`) and functions
////       refer to it (`[Repo::Storage]`). Psalm spells the alias with `@psalm-type`
////       (and `@psalm-import-type` from other classes) inside `@psalm-capabilities`.
////       The effect is IO because Hack's parse-level property-write check cannot
////       resolve a context constant.

final class Repo {
  const ctx Storage = [defaults];
  public function save()[self::Storage]: void { echo "saved"; }
}

final class Service {
  public function run(Repo $r)[Repo::Storage]: void { $r->save(); }
}
