# AssignmentToVoid

Emitted when assigning from a function that returns `void`:

```php
function foo() : void {}
$a = foo();
```

#### How to fix

You should just be able to remove the assignment:

```php
function foo() : void {}
foo();
```
