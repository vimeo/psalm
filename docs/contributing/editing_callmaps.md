# Altering callmaps

## Intro

One of the first things most contributors start with is proposing changes to
callmaps.

Callmap is a data file (formatted as a PHP file returning an array) that tells
Psalm what arguments function/method takes and what it returns. Psalm loads
`dictionaries/CallMap_<PHP major version><PHP minor version>.php` for the analyzed
PHP version (`CallMap_84.php` for PHP 8.4).

These files are generated: never edit them by hand. `bin/stubs/gen_callmap.php` builds
them from two sources:

* `dictionaries/autogen/CallMap_*.php`: the signatures read by reflection from PHP and
  a set of extensions, one file per PHP version;
* `dictionaries/override/`: hand written corrections. `CallMap.php` holds the
  signatures of the newest PHP version, and the delta files `CallMap_*_delta.php`
  hold how they changed in each version.

An override entry wins over the reflected one. Overrides equal to the reflected
signature in every version are dropped.

## Changing a signature

1. Edit the entry in `dictionaries/override/CallMap.php` (to add a missing one, copy it
   from `dictionaries/autogen/CallMap_$latest.php`).
2. Run `php -d memory_limit=-1 bin/stubs/gen_callmap.php`. It regenerates
   `dictionaries/CallMap_*.php` and rewrites the delta files.

The edit applies to every older version up to the first delta that changes the
function. Narrowing the parameter of `array_key_first` this way changes
`CallMap_80.php` to `CallMap_86.php` and the `new` side of its `changed` entry in
`CallMap_80_delta.php`, while `CallMap_74.php` keeps the `old` side. To change an
older signature, edit the `old` side of the entry in that delta.

## Regenerating the reflected callmaps

`bin/stubs/gen_callmap.sh` (`composer gen_callmap`, part of `composer build`) runs
`bin/stubs/gen_base_callmap.php` inside one Docker image per PHP version
(`ghcr.io/<owner>/psalm:internal_stubs_<version>`), then `gen_callmap.php`.
`bin/stubs/build_docker.php` builds those images and generates their Dockerfile on
the fly.

To add the types of an extension, add it to `EXTENSIONS` in `bin/stubs/build_docker.php`
(or to `INCLUDES`, for some PHP versions only) with its version source in `SOURCES`.
`PIE_PACKAGES` lists the extensions installed with PIE, and `EXCLUDES` the ones that
don't build on a PHP version yet.

Every push runs the "Build docker image for stubs" workflow: it builds the images,
runs `composer build`, and fails when a committed file is out of date, uploading the
difference as the `composer-build-patch` artifact. `bin/ci/apply-composer-build-patch.sh`
applies it to your checkout (set `PSALM_REPO=<you>/psalm` for a run on your fork).

## Checking a callmap against reflection

`composer verify-callmap` runs `tests/Internal/Codebase/InternalCallMapHandlerTest.php`, which compares the
callmap with what reflection of the installed PHP and its extensions says.

Functions that don't pass yet are listed in that test (`$ignoredFunctions`, and for return types only
`$ignoredReturnTypeOnlyFunctions`). To fix one:

1. Remove its entry from the ignore list and run `composer verify-callmap`.
2. Fix the errors it reports in the callmap (and in the deltas, if the signature changed in an older version).
3. Run `composer verify-callmap` again, then open the PR.

An entry that is already right must not be on the list: the test fails with `Remove 'name' from
InternalCallMapHandlerTest::$ignoredFunctions`.

## Full callmap format

A callmap (`dictionaries/CallMap_*.php`, and `dictionaries/override/CallMap.php`) has function/method
names as keys and an array representing the corresponding signature as a value.

First element of that value is a return type (it also doesn't have a key), and
subsequent elements represent function/method parameters. Parameter name for an
optional parameter is postfixed with `=`, references with `&`/`&r_`/`&w_`/`&rw_`
(depending on the read/write meaning of the reference param) and 
variadic args are prefixed with `...`.

Callmaps also support function aliases: aliases are very useful to specify that
a certain function behaves differently according to the parameter types.

For example, the following aliases are currently in use in the callmap to specify 
that the `version_compare` function can be called with an `operator` parameter, 
in which case it will return a boolean; otherwise it will return an integer.  

```php
[
    'version_compare' => [
        0 => 'bool',
        'version1' => 'string',
        'version2' => 'string',
        'operator' => '\'!=\'|\'<\'|\'<=\'|\'<>\'|\'=\'|\'==\'|\'>\'|\'>=\'|\'eq\'|\'ge\'|\'gt\'|\'le\'|\'lt\'|\'ne\'|null',
    ],
    'version_compare\'1' => [
        0 => 'int',
        'version1' => 'string',
        'version2' => 'string',
    ],
]
```

**Note**: the above example doesn't provide almost any useful information for type inference;
in fact, the real logic for return type inference is contained in the `VersionCompareReturnTypeProvider`.  

The callmap is mainly useful when treating functions and methods as *callable values*, for example:

```php
<?php
function naive_version_compare(string $a, string $b, ?string $operator = null): int|bool {
    return 0;
}

$a = ["1.0", "2.0"];

// OK
usort($a, "version_compare");

// InvalidArgument: Argument 2 of usort expects callable(string, string):int, but impure-callable(string, string, null|string=):(bool|int) provided
usort($a, "naive_version_compare");
```

The first usort succeeds, because psalm chooses the correct alias to use between the two provided in the callmap.  
The second usort fails (equivalent to the non-split return type of `version_compare` inferred by reflection), because the return type is a union of the two possible signatures of version_compare.  

When you have multifaceted functions like these, it's a very good idea to at least define a templated stub in `stubs/` for them, or a custom return type provider for even more complex logic, not representable with templates/conditional types/etc in a stub.

Also note that `bin/stubs/gen_callmap.php` has some validation logic which will re-add back removed parameters in overridden aliased callmaps: to avoid this, explicitly whitelist aliased functions by editing `assertParameter` in `bin/stubs/gen_callmap_utils.php`, and eventually `bin/stubs/gen_callmap.php` as needed.

## Delta file format

Delta files (named `CallMap_<PHP major version><PHP minor version>_delta.php`)
list changes that happened in the corresponding PHP version. There are
three section with self-explanatory names: `added` (for functions/methods that
were added in that PHP version), `removed` (for those that were removed) and
`changed`.

Entry format for `removed` and `added` section matches that of a full callmap,
while `changed` entries list `old` and `new` signatures.

## How the generator uses delta files

`bin/stubs/gen_callmap.php` starts from `dictionaries/override/CallMap.php` (the
newest PHP version) and applies each delta in reverse to get the overrides of the
version before it: entries in `added` are removed, those in `removed` are added
back, and `changed.new` signatures are replaced with `changed.old`. It merges the
overrides of each version over the reflected callmap of that version, then rewrites
the delta files from the result, so they always match `override/CallMap.php`.

The "Build docker image for stubs" workflow fails when a committed callmap differs
from what the generator produces.

## Adding a function

The functions and methods of PHP and of the extensions in the stub images come from
reflection, so a new one reaches the callmaps without an override: `array_is_list()`
(PHP 8.1) has no override entry. Add one only to refine its types, as described in
[Changing a signature](#changing-a-signature).
