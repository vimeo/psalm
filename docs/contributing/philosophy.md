# Philosophy

This is about why Psalm is the way it is. This is a living document!

## Psalm is a tool for PHP written in PHP

### Advantages

- PHP is fast enough for most use-cases
- Writing the tool in PHP guarantees that PHP community members can contribute to it without too much difficulty

### Drawbacks

- Psalm is slow in very large monorepos (over 5 million lines of checked-in code).
- Psalm’s language server is more limited in what it can provide than comparable compiled-language tools. For example, it's infeasible to find/change all occurrences of a symbol, in all files that use it, as you type.

### Comparison to other languages & tools

Many languages have typecheckers/compilers written in the same language. Popular examples include Go, Rust, and TypeScript. Python is a special case where the semi-official typechecker [MyPy](https://github.com/python/mypy) (written in Python) can also [compile to a C Python extension](https://github.com/python/mypy/blame/master/mypyc/README.md#L6-L10), which runs 4x faster than the interpreted equivalent.

Some interpreted languages have unofficial open-source typecheckers written in faster compiled languages. In all cases a single mid-to-large company is behind each effort, with a small number of contributors not employed by that company:

- PHP
    - [NoVerify](https://github.com/VKCOM/noverify) – written in Go. Runs much faster than Psalm (but does not support many modern PHP features)
- Ruby
    - [Sorbet](https://sorbet.org/) - written in C
- Python
    - [Pyre](https://github.com/facebook/pyre-check) - written in OCaml
- [Hack](https://github.com/facebook/hhvm) - the typechecker is written in OCaml and Rust

## Psalm's primary purpose is finding bugs

Psalm does a lot, but people mainly use it to find potential bugs in their code.

All other functionality – the language server, security analysis, manipulating/fixing code is a secondary concern.

## It's designed to be run on syntactically-correct code

Psalm is almost always run on PHP code that parses a lint check (`php -l <filename>`) – i.e. syntactically-correct code. Psalm is not a replacement for that syntax check.

Given Psalm is almost always used on syntatically-correct code it should use a parser built for that purpose, and `nikic/php-parser` is the gold-standard.

Where Psalm needs to run on syntactically-incorrect code (e.g. in language server mode) Psalm should still use the same parser (and work around any issues that it produces).

## Docblock annotations are better than type-providing plugins

Psalm offers a plugin API that allows you to tell it what about your program's property types, return types and parameter types.

Psalm aims to operate in a space with other static analysis tools. The more users rely on plugins, the less chance those other tools have to understand the user's intent.

Psalm should encourage developers to use docblock annotations rather than type-providing plugins. This was a driving force in the adoption of [Conditional Types](../annotating_code/type_syntax/conditional_types.md) which allowed Psalm to replace some of its own internal type-providing plugins.

The other benefit to docblock annotations is verifiability – for the most part Psalm is able to verify that docblock annotations are correct, but it cannot provide many assurances when plugins are used.

This doesn’t mean that plugins as a whole are bad, or that they can’t provide useful types. A great adjacent use of plugins is to provide stubs with Psalm type annotations for libraries that don’t have them. This helps the PHP ecosystem because those stubs may eventually make their way into the project currently being stubbed.

## Docblock annotations that can be verified are better than those that cannot

Psalm currently supports a number of function/class docblock annotations that it's unable to verify:

- `@psalm-assert`, `@psalm-assert-if-true`, `@psalm-assert-if-false`
- `@property`, `@method`, `@mixin`

Whenever new docblock annotations are added, effort should be made to allow Psalm to verify their correctness.

## In certain circumstances docblock annotations are better than PHP 8 attributes

For information that's just designed to be consumed by static analysis tools, docblocks are a better home than PHP 8 attributes.

A rationale is provided in [this article](https://psalm.dev/articles/php-8-attributes).
