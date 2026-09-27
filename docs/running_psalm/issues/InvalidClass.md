# InvalidClass

Emitted by plugins (e.g. the example `StringChecker` plugin) when a class is referenced incorrectly.

Psalm itself no longer emits this issue: class names are resolved case-sensitively, so referencing a
class with the wrong casing (e.g. `new foo()` for `class Foo {}`) is reported as `UndefinedClass`.
