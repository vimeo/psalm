# UnsafeGenericInstantiation

Emitted when an attempt is made to instantiate a class using `new static` without a constructor that's final:

```php
<?php

/**
 * @template T
 * @psalm-consistent-constructor
 */
class Container {
    /**
     * @var T
     */
    public $t;

    /**
     * @param T $t
     */
    public function __construct($t) {
        $this->t = $t;
    }

    /**
     * @template U
     * @param U $u
     * @return static<U>
     */
    public function getInstance($u) : static
    {
        return new static($u);
    }
}
```


## What’s wrong here?

The problem comes when extending the class:

```php
<?php

/**
 * @template T
 * @psalm-consistent-constructor
 */
class Container {
    /**
     * @var T
     */
    public $t;

    /**
     * @param T $t
     */
    public function __construct($t) {
        $this->t = $t;
    }

    /**
     * @template U
     * @param U $u
     * @return static<U>
     */
    public function getInstance($u) : static
    {
        return new static($u);
    }
}

/**
 * @extends Container<string>
 */
class StringContainer extends Container {}

$c = StringContainer::getInstance(new stdClass());
// creates StringContainer<stdClass>, clearly invalid
```

## How to fix

Either use `new self` instead of `new static`:

```php
<?php

/**
 * @template T
 * @psalm-consistent-constructor
 */
class Container {
    /**
     * @var T
     */
    public $t;

    /**
     * @param T $t
     */
    public function __construct($t) {
        $this->t = $t;
    }

    /**
     * @template U
     * @param U $u
     * @return self<U>
     */
    public function getInstance($u) : self
    {
        return new self($u);
    }
}
```

Or you can add a `@psalm-consistent-templates` annotation which ensures that any child class has the same generic params:

```php
<?php

/**
 * @template T
 * @psalm-consistent-constructor
 * @psalm-consistent-templates
 */
class Container {
    /**
     * @var T
     */
    public $t;

    /**
     * @param T $t
     */
    public function __construct($t) {
        $this->t = $t;
    }

    /**
     * @template U
     * @param U $u
     * @return static<U>
     */
    public function getInstance($u) : static
    {
        return new static($u);
    }
}

/**
 * @template T
 * @psalm-extends Container<T>
 */
class LazyLoadingContainer extends Container {}
```
