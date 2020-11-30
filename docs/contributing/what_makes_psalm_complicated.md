# Things that make developing Psalm complicated

This is a somewhat informal list that might aid others.

## Type inference

Type inference is one of the big things Psalm does. It tries to figure out what different PHP elements (function calls, if/for/foreach statements etc.) mean for the data in your code.

Within type inference there are a number of tricky areas:

#### Loops

Loops are hard to reason about - break and continue are a pain. This analysis mainly takes place in `LoopAnalyzer`

#### Combining types

There are lots of edge-cases when combining types together, given the many types Psalm supports. Type combining occurs in `TypeCombiner`.

#### Logical assertions

What effect do different PHP elements have on user-asserted logic in if conditionals, ternarys etc. This logic is spread between a number of different classes.

#### Generics & Templated code

Figuring out how templated code should work (`@template` tags) and how much it should work like it does in other languages (Hack, TypeScript etc.) is tricky. Psalm also supports things like nested templates (`@template T1 of T2`) which makes things trickier

## Detecting dead code

Detecting unused variables requires some fun [data-flow analysis](https://psalm.dev/articles/better-unused-variable-detection).

Detecting unused classes and methods between different runs requires maintaining references to those classes in cache (see below).

## Supporting the community
- **Supporting formal PHPDoc annotations**
- **Supporting informal PHPDoc annotations**
  e.g. `ArrayIterator|string[]` to denote an `ArrayIterator` over strings
- **non-Composer projects**
  e.g. WordPress

## Making Psalm fast

#### Parser-based reflection

Requires scanning everything necessary for analysis

#### Forking processes** (non-windows)

Mostly handled by code borrowed from Phan, but can introduce subtle issues, also requires to think about how to make work happen in processes

#### Caching thing

see below

## Cache invalidation

#### Invalidating analysis results

Requires tracking what methods/properties are used in what other files, and invalidating those results when linked methods change

#### Partial parsing

Reparsing bits of files that have changed, which is hard

## Language Server Support

#### Handling temporary file changes

When files change Psalm figures out what's changed within them to avoid re-analysing things unnecessarily

#### Dealing with malformed PHP code

When people write code, it's not always pretty as they write it. A language server needs to deal with that bad code somehow

## Fixing code with Psalter

#### Adding/replacing code

Figuring out what changed, making edits that could have been made by a human

#### Minimal diffs

hard to change more than you need
