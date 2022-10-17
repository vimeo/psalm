# Altering callmaps

## Intro

One of the first things most contributors start with is proposing changes to
callmaps.

Callmap is a data file (formatted as a PHP file returning an array) that tells
Psalm what arguments function/method takes and what it returns.

There are two full callmaps (`CallMap.php` and `CallMap_historical.php`) in
`dictionaries` folder, and a number of delta files that provide information on
how signatures changed in various PHP versions. `CallMap_historical` has
signatures as they were in PHP 7.0, `CallMap.php` contains current signatures
(for PHP 8.1 at the time of writing).

## Full callmap format

Full callmaps (`CallMap.php` and `CallMap_historical.php`) have function/method
names as keys and an array representing the corresponding signature as a value.

First element of that value is a return type (it also doesn't have a key), and
subsequent elements represent function/method parameters. Parameter name for an
optional parameter is postfixed with `=`.

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
we currently have in Psalm. This will need a change to `CallMap_historical.php`
(as the signature was always that way) and `CallMap.php` (as the signature is
still valid). Here's [the PR that does it](https://github.com/vimeo/psalm/pull/6359/files).

If function signature is correct for an older version but has changed since you
will need to edit the delta for PHP version where signature changed and
`CallMap.php` (as this new signature is still valid).  Here's
[the PR that does it (makes `timestamp` nullable)](https://github.com/vimeo/psalm/pull/6244/files).
