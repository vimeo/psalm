<?php
namespace PHPSTORM_META {

    override(\Ns\MyClass::create(0), map([
        '' => '@',
        'exception' => \Exception::class,
        'object' => \stdClass::class,
    ]));
    override(\create(0), map([
        '' => '@',
        'exception' => \Exception::class,
        'object' => \stdClass::class,
    ]));

    override(\Ns\MyClass::foo(0), type(0));
    override(\Ns\MyClass::bar(0), elementType(0));
    override(\foo(0), type(0));
    override(\bar(0), elementType(0));
}
