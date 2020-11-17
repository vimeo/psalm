# TaintedUserSecret

Emitted when tainted input detection is turned on and data marked as a user secret is detected somewhere it shouldn’t be.

```php
<?php

class User {
    /**
     * @psalm-taint-source user_secret
     */
    public function getPassword() : string {
        return "$omePa$$word";
    }
}

function showUserPassword(User $user) {
    echo $user->getPassword();
}
```
