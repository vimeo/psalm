The entry point for all analysis is [`ProjectChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/ProjectChecker.php)

`ProjectChecker` is in charge of two things: Scanning and Analysis

## Scanning

For any file or set of files, Psalm needs to determine all the possible dependencies and get their function signatures and constants, so that the analysis phase can be done multithreaded.

It uses a custom `NodeVisitor` called [`DependencyFinderVisitor`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Visitor/DependencyFinderVisitor.php) which has two modes when scanning a given file: a shallow scan, where it just gets function signatures, return types, constants, and inheritance, or a deep scan, where it drills into every function statement to get those dependencies too (e.g. the class names instantiated by a function). It only does a deep scan on files that it knows will be analysed later (so the vast majority of the vendor directory, for example, just gets a shallow scan).

For each file that `DependencyFinderVisitor` visits, Psalm creates a `FileStorage` instance, along with `ClassLikeStorage` and `FunctionLikeStorage` instances depending on the file contents.

To figure out the `ClassName `=> `src/FileName.php` mapping it uses reflection for project files and the Composer classmap for vendor files.

Once we have a set of all files and their classes and function signatures, we calculate inheritance for everything (the `populate...Storage` methods) and then move onto analysis.

## Analysis

We analyse files in [`FileChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/FileChecker.php)

The `FileChecker` takes a given file and looks for a set of top-level components: classes, traits, interfaces, functions. It can look inside namespaces and extract the classes, interfaces, traits and functions in them as well.

It delegates the analysis of those components to [`ClassChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/ClassChecker.php), [`InterfaceChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/InterfaceChecker.php) and [`FunctionChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/FunctionChecker.php).

Because it’s the most basic use case for the line-by-line analysis (no class inheritance to worry about), let’s drill down into `FunctionChecker`.

### Function Analysis

`FunctionChecker::analyze` is defined in [`FunctionLikeChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/FunctionLikeChecker.php). That method first gets the `FunctionLikeStorage` object for the given function that we created in our scanning step. That `FunctionLikeStorage` object has information about function parameters, which we then feed into a [`Context`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Context.php) object. The `Context` contains all the type information we know about variables & properties (stored in `Context::$vars_in_scope`) and also a whole bunch of other information that can change depending on assignments and assertions.

Somewhere in `FunctionLikeChecker::analyze` we create a new [`StatementsChecker`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Checker/StatementsChecker.php) and then call its `analyze()` method, passing in a set of PhpParser nodes. `StatementChecker::analyze` passes off to a number of different checkers (`IfChecker`, `ForeachChecker`, `ExpressionChecker` etc.) for more thorough analysis.

At each line the `Context` object may or may not be manipulated. At branching points (if statements, loops, ternary etc) the `Context` object is cloned and then, at the end of the branch, Psalm figures out how to resolve the changes and update the uncloned `Context` object.

Each PhpParser node is then abused, adding a property called `inferredType` which Psalm uses for type analysis.

After all the statements have been analysed we gather up all the return types and compare to the given return type.

### Type Reconciliation

While some updates to the `Context` object are straightforward, others are not. Updating the `Context` object in the light of new type information happens in [`Reconciler`](https://github.com/vimeo/psalm/blob/master/src/Psalm/Type/Reconciler.php), which takes an array assertions e.g. `[“$a” => “!null”]` and a list of existing type information e.g. `$a => string|null` and return a set of updated information e.g. `$a => string`
