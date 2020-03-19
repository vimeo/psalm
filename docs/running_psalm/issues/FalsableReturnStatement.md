# FalsableReturnStatement

Emitted if a return statement contains a false value, but the function return type does not allow false

```php
function foo() : string {
    if (rand(0, 1)) {
        return "foo";
    }

    return false; // emitted here
}
```

