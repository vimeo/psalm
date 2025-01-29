# Altering callmaps

## Intro

One of the first things most contributors start with is proposing changes to
callmaps.

Callmap is a data file (formatted as a PHP file returning an array) that tells
Psalm what arguments function/method takes and what it returns.

There is a partial callmap file in `dictionaries/override/CallMap.php` folder, and
a number of delta files that provide information on
how signatures changed in various PHP versions in `dictionaries/override/CallMap_*_delta.php`. 

The callmap files in the `override` subfolder are then used, in conjunction with the
automatically-generated reflection callmaps in `dictionaries/autogen`, to generate the
final callmaps in `dictionaries/CallMap_*`, which are the actual callmaps in use by Psalm.

After editing an override callmap, run `bin/gen_callmap.php` to regenerate the final callmap file.

To also regenerate the base callmaps, run `bin/gen_callmap.sh`: it will use the dockerfiles in `bin/` 
to extract types from PHP and a set of extensions.  
To add types from an extension to the callmap, edit all versions of the Dockerfiles.  

## Full callmap format

The full callmap (`CallMap.php`) has function/method
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

Also note that `bin/gen_callmap.php` has some validation logic which will re-add back removed parameters in overridden aliased callmaps: to avoid this, explicitly whitelist aliased functions by editing `assertParameter` in `bin/gen_callmap_utils.php`, and eventually `bin/gen_callmap.php` as needed.

## Delta file format

Delta files (named `CallMap_<PHP major version><PHP minor version>_delta.php`)
list changes that happened in the corresponding PHP version. There are
three section with self-explanatory names: `added` (for functions/methods that
were added in that PHP version), `removed` (for those that were removed) and
`changed`.

Entry format for `removed` and `added` section matches that of a full callmap,
while `changed` entries list `old` and `new` signatures.

## How Psalm uses delta files

When the current PHP version is set to something other than the latest PHP
version supported by Psalm, it needs to process delta files to arrive at a
version of callmap matching the one that is used during analysis. Psalm uses
the following process to do that:

1. Read `CallMap.php` (Note: it's the one having the latest signatures).
2. If it matches configured PHP version, use it.
3. If the callmap delta for previous PHP version exists, read that.
4. Take previous callmap delta and apply it in reverse order. That is, entries
   in `removed` section are added, those in `added` section are removed and
   `changed.new` signatures in the current callamp are replaced with
   `changed.old`.
5. Goto 2

## Consistent histories

To make sure there are no mismatches in deltas and the callmap, CI validates
that all function/method entries have consistent histories. E.g. that the
signature in `changed.new` matches the one in `CallMap.php`, the `removed`
entries are actually absent from `CallMap.php` and so on.

## Typical changes

To put that into practical perspective, let's see how a couple of typical
callmap changes may look like.

### Adding a new function

Say, there's a function added in PHP 8.1, e.g. `array_is_list()`. Add it to the
`CallMap_81_delta.php` (as it was introduced in PHP 8.1), and `CallMap.php` (as
it exists in the latest PHP version). Here's [the PR that does it](https://github.com/vimeo/psalm/pull/6398/files).

### Correcting the function signature

Assume you found an incorrect signature, the one that was always different to what
we currently have in Psalm. This will need a change to `CallMap.php` (as the signature is
still valid). Here's [the PR that does it](https://github.com/vimeo/psalm/pull/6359/files).

If function signature is correct for an older version but has changed since you
will need to edit the delta for PHP version where signature changed and
`CallMap.php` (as this new signature is still valid).  Here's
[the PR that does it (makes `timestamp` nullable)](https://github.com/vimeo/psalm/pull/6244/files).
