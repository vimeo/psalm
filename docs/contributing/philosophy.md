# Philosophy

This is about why Psalm is the way it is. This is a living document!

## 1. Psalm is a tool for PHP written in PHP

- PHP is fast enough for most use-cases
- Writing the tool in PHP guarantees that PHP community members can contribute to it without too much difficulty

### Drawbacks

- Psalm is slow in very large monorepos (over 5 million lines of checked-in code).
- Psalm’s language server is more limited in what it can provide than comparable compiled-language tools. For example, it's infeasible to find/change all occurences of a symbol, in all files that use it, as you type.

### Comparison to other languages & tools

Many languages have typecheckers/compilers written in the same language. Popular examples include Go, Rust, and TypeScript.

Python is a special case where the semi-official typechecker [MyPy can also compile to a C Python extension](https://github.com/python/mypy/blame/master/mypyc/README.md#L6-L10), which runs 4x faster than the interpreted equivalent.

Some interpreted languages have unofficial typecheckers written in faster compiled languages. In all cases a single mid-to-large company is behind each effort, with a small number of contributors not employed by that company:

- PHP
    - [NoVerify](https://github.com/VKCOM/noverify) – written in Go. Runs much faster than Psalm (but does not support many modern PHP features)
    - PhpStorm – closed-source tool written in Java
- Ruby
    - [Sorbet](https://sorbet.org/) - written in C
- Python
    - [Pyre](https://github.com/facebook/pyre-check) - written in OCaml
- [Hack](https://github.com/facebook/hhvm) - the typechecker is written in OCaml and Rust

## 2. Psalm's primary purpose is finding bugs

Psalm does a lot, but the main thing people use it for is to find potential bugs in their code.

## 3. Psalm’s primary execution environment is the command line

Psalm is mostly run on PHP code that parses a lint check (`php -l`). Psalm does not replace that check for verifying PHP syntax.

Since most of the time Psalm is used on syntatically-correct code it should use a parser built for that purpose, and `nikic/php-parser` is the best candidate.

## 4. Annotations are better than type-providing plugins

Psalm offers a plugin API that allows you to tell it what about your program's property types, return types and parameter types.

Psalm aims to operate in a space with other static analysis tools. The more users rely on plugins, the less chance those other tools have to understand the user's intent.

So wherever possible Psalm should encourage developers to use annotations rather than type-providing plugins. This was a driving force in the adoption of [Conditional Types](../annotating_code/type_syntax/conditional_types.md) which allowed Psalm to replace some of its own internal type-providing plugins.

## 5. In certain circumstances docblocks > PHP 8 attributes

For information that's just designed to be consumed by static analysis tools, docblocks are a better home than PHP 8 attributes.

A rationale is provided in [this article](https://psalm.dev/articles/php-8-attributes).
