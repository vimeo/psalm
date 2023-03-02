# Contributing to Psalm

Psalm is made possible through the contributions of [hundreds of developers](https://github.com/vimeo/psalm/graphs/contributors).

Hopefully you can be one of them?

## Getting started

[Here’s a rough guide to the codebase](how_psalm_works.md).

[Here's the philosophy underpinning the Psalm’s development](philosophy.md).

I've also put together [a list of Psalm’s complexities](what_makes_psalm_complicated.md).

Are you looking for low-hanging fruit? Here are some [GitHub issues](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22easy+problems%22) that shouldn't be too difficult to resolve.

### Don’t be afraid!

One great thing about working on Psalm is that it’s _very_ hard to introduce any sort of type error in Psalm’s codebase. There are almost 5,000 PHPUnit tests, so the risk of you messing up (without the CI system noticing) is very small.

### Why static analysis is cool

Day-to-day PHP programming involves solving concrete problems, but they're rarely very complex. Psalm, on the other hand, attempts to solve a pretty hard collection of problems, which then allows it to detect a ton of bugs in PHP code without actually executing that code.

There's a lot of interesting theory behind the things Psalm does, too. If you want you can go very deep, though you don't need to know really any theory to improve Psalm.

Lastly, working to improve static analysis tools will also make you a better PHP developer – it'll help you think more about how values flow through your program.

### Guides

* [Editing callmaps](editing_callmaps.md)
* [Adding a new issue type](adding_issues.md)

## Pull Requests

Before you send a pull request, make sure you follow these guidelines:

Run integration checks locally: `composer tests`

If you're adding new features or fixing bugs, don’t forget to add tests!
