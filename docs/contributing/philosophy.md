# Philosophy

This is about why Psalm is the way it is. This is a living document!

## Psalm is a tool for PHP written in PHP

### Advantages

- PHP is fast enough for most use-cases
- Writing the tool in PHP guarantees that PHP community members can contribute to it without too much difficulty

### Drawbacks

- Psalm is slow in very large monorepos (over 5 million lines of checked-in code).
- Psalm’s language server is more limited in what it can provide than comparable compiled-language tools. For example, it's infeasible to find/change all occurences of a symbol, in all files that use it, as you type.

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

## Psalm’s primary execution environment is the command line

Psalm is mostly run on PHP code that parses a lint check (`php -l`). Psalm does not replace that check for verifying PHP syntax.

Given Psalm is almost always used on syntatically-correct code it should use a parser built for that purpose, and `nikic/php-parser` is the gold-standard.

## Annotations are better than type-providing plugins

Psalm offers a plugin API that allows you to tell it what about your program's property types, return types and parameter types.

Psalm aims to operate in a space with other static analysis tools. The more users rely on plugins, the less chance those other tools have to understand the user's intent.

Psalm should encourage developers to use docblock annotations rather than type-providing plugins. This was a driving force in the adoption of [Conditional Types](../annotating_code/type_syntax/conditional_types.md) which allowed Psalm to replace some of its own internal type-providing plugins.

The other benefit to docblock annotations is verifiability – for the most part Psalm is able to verify that docblock annotations are correct, but it cannot provide many assurances when plugins are used.

## In certain circumstances docblocks > PHP 8 attributes

For information that's just designed to be consumed by static analysis tools, docblocks are a better home than PHP 8 attributes.

A rationale is provided in [this article](https://psalm.dev/articles/php-8-attributes).
