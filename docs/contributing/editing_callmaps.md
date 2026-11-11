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
`bin/stubs/gen_base_callmap.php` inside one Docker image per PHP version, then
`gen_callmap.php`. `bin/stubs/build_docker.php` generates the Dockerfile of each image
from `bin/stubs/image_lock.php`, which pins every input of the images:

```php
'installer' => '2.13.2@sha256:27fa…',           // the install-php-extensions release
'phpstorm-stubs' => '813c2107…',                // the commit gen_base_callmap.php downloads stubs from
'versions' => [
    '8.4' => [
        'php' => '8.4.27@sha256:d878…',         // php:8.4-alpine, by digest
        'extensions' => [
            'apcu' => 'pie@apcu/apcu:v5.1.28',  // installed with PIE
            'event' => 'pecl@3.2.0',            // installed with install-php-extensions
            'zmq' => 'git@616b6c64…:1.1.3',     // installed from a commit
        ],
    ],
],
```

An image fails to build unless PHP and each of these extensions report their locked
version. Its tag is `internal_stubs_<version>-<hash of its Dockerfile>`.

`dictionaries/autogen/CallMapInputs.php` stamps the reflected callmap of each version
with the inputs it comes from. `gen_callmap.sh` only reflects the versions whose stamp
doesn't match (`php bin/stubs/build_docker.php --stale-plain` lists them):

| Change | Versions reflected again |
| --- | --- |
| `dictionaries/override/`, `src/`, `gen_callmap.php` | none (`composer build` needs no Docker) |
| a new or changed `PINS['8.4']`, or an extension added to `INCLUDES['8.4']` | 8.4 |
| `gen_base_callmap.php`, or an extension added to `EXTENSIONS` | all |
| a hand edit of `dictionaries/autogen/CallMap_84.php` | 8.4 |

To add the types of an extension, add it to `EXTENSIONS` in `bin/stubs/build_docker.php`
(or to `INCLUDES`, for some PHP versions only) with its version source in `SOURCES`.
`PIE_PACKAGES` lists the extensions installed with PIE, `EXCLUDES` the ones that
don't build on a PHP version yet, and `PINS` the versions held back. Removing a `PINS`
entry keeps the pinned version in the lock until the next refresh.

Every push runs the "Build docker image for stubs" workflow. It adds the inputs the lock
lacks (the newest version of an added extension), builds the images of the stale
versions under `ghcr.io/<owner>/psalm` of the repository it runs in (your fork for a
fork), runs `composer build`, and commits the difference to the pushed branch. Pull before
pushing again. If the commit can't be pushed, `bin/ci/apply-composer-build-patch.sh`
applies the patch to your checkout (set `PSALM_REPO=<you>/psalm` for a run on your fork).
To reflect locally, point `gen_callmap.sh` at the images the workflow built:
`STUBS_IMAGE_OWNER=<you> composer gen_callmap`. The `Check PHP syntax` CI job fails
while a stamp doesn't match.

New releases of PHP, the extensions and install-php-extensions reach the lock only in a
refresh: the weekly scheduled run of the workflow, or a manual run with `refresh`
checked. It resolves every input again and opens a pull request from
`stubs-refresh/<branch>` with the lock and callmap changes. On master (and on 6.x once it
gets this flow), any lock or reflected callmap change goes to that pull request instead of
being pushed. Opening it
needs "Allow GitHub Actions to create and approve pull requests" in the repository
settings; otherwise the run fails with a link to open it by hand.

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

Each entry keeps its analysis (or "doc") return type at index `0`. The optional
index `-1` records the native or tentative return type extracted from PHP:

```php
'strlen' => [
    0 => 'int<0, max>',
    -1 => 'int',
    'string' => 'string',
],
'amqpvalue::toamqpvalue' => [
    0 => 'mixed',
],
'jsonserializable::jsonserialize' => [
    -1 => 'mixed',
    -2 => 'tentative',
],
'limititerator::getposition' => [ // PHP 8.0: no native or tentative return signature
    0 => 'int',
    -2 => 'none',
],
```

Only the signature return type imposes a native return-type requirement on an
implementation. `-1 => 'mixed'` is an explicit signature, not an absent type.
Optional index `-2` records its provenance:

| `-1` | `-2` | Meaning |
| --- | --- | --- |
| present | absent | Native return signature |
| present | `tentative` | Tentative return signature, available since PHP 8.1 |
| absent | `none` | Reflection confirmed there is no return signature in this PHP version |
| absent | absent | Not reflected by the generator; the stub or runtime may provide a signature |

`#[ReturnTypeWillChange]` suppresses tentative return-signature checks, including
an explicitly incompatible return type. It does not suppress a native signature.
Stubs and index `0` still provide the types used to analyse calls; PHPDoc
compatibility checks are separate from native signature checks.

Generated files omit index `0` when its type equals index `-1`; the signature then
also supplies the analysis return type. Read it with `$entry[0] ?? $entry[-1]`;
`getCallMap()` preserves the compact entries without recreating index `0`.

Edit the analysis return type in the overrides, then regenerate. Signature return
types come from reflection and are retained independently of overrides and
alternate signatures; the generator does not copy analysis refinements into them.

### Reflection coverage

Each image must load every extension in the version-specific manifest returned by
`php bin/stubs/build_docker.php --extensions-plain <version>`. Generation fails
before writing a callmap if its PHP version or a required extension is wrong.
`dictionaries/autogen/CallMapExtensions_*.php` records the extension of each
reflected API. These manifests describe coverage; they do not list every PHP extension.
For example, the current images do not load `bz2`, `calendar` or `dba`.
Their handwritten entries can therefore lack reflected signature metadata.

The merger preserves overrides for APIs absent from an image. An untyped downloaded
PHPStorm stub does not establish that an extension has no native return type.
For an API without generated metadata, Psalm first uses its stub signature. Runtime
reflection can supply a missing signature only when the analysed PHP major/minor
matches the PHP running Psalm; it never imports a newer runtime signature into an
older target. Results for these uncovered APIs can consequently depend on the
installed extensions and their versions. A different target version needs a stub
or generated metadata for that version.

A callmap (`dictionaries/CallMap_*.php`, and `dictionaries/override/CallMap.php`) has function/method
names as keys and an array representing the corresponding signature as a value.

Entries with string keys represent function/method parameters. Parameter name for an
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
