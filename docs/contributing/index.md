# Contributing to Psalm

Psalm is made possible through the contributions of almost 200 developers.

Hopefully you can be one of them?

## Getting started

[Here’s a rough guide to the codebase](how_psalm_works.md).

I've also put together [a list of Psalm’s complexities](what_makes_psalm_complicated.md).


## Pull Requests

Before you send a pull request, make sure you follow these guidelines:

Run integration checks locally:

- `composer phpcs` - checks the code is properly linted
- `vendor/bin/paratest` - runs PHPUnit tests in parallel
- `./psalm` - runs Psalm on itself

If you're adding new features or fixing bugs, don’t forget to add tests!
