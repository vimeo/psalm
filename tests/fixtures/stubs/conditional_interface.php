<?php
if (interface_exists(Foo::class)) {
    interface I1 extends Foo {}
} else {
    interface I1 {}
}

if (!interface_exists(Foo::class)) {
    interface I2 {}
} else {
    interface I2 extends Foo {}
}

if (interface_exists('Bar')) {
    interface I3 extends Bar {}
} else {
    interface I3 {}
}

if (!interface_exists('Bar')) {
    interface I4 {}
} else {
    interface I4 extends Bar {}
}

if (interface_exists(Throwable::class)) {
    interface I5 extends Throwable {}
} else {
    interface I5 {}
}

if (!interface_exists(Throwable::class)) {
    interface I6 {}
} else {
    interface I6 extends Throwable {}
}

