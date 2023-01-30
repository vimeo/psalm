<?php
namespace PHPSTORM_META {

    // tests with argument offset (0)
    override(\Ns\MyClass::crEate(0), map([
        '' => '@',
        'exception' => \Exception::class,
        'object' => \stdClass::class,
    ]));
    override(\create(0), map([
        '' => '@',
        'exception' => \Exception::class,
        'object' => \stdClass::class,
    ]));

    // tests without argument offset (0 by default)
    override(\Ns\MyClass::crEate2(), map([
        '' => '@',
        'exception' => \Exception::class,
        'object' => \stdClass::class,
    ]));
    override(\create2(), map([
        '' => '@',
        'exception' => \Exception::class,
        'object' => \stdClass::class,
    ]));

    // tests with class constant as key
    override(\Ns\MyClass::crEate3(), map([
        '' => '@',
        \Ns\MyClass::EXCEPTION => \Exception::class,
        \Ns\MyClass::OBJECT => \stdClass::class,
    ]));

    override(\Ns\MyClass::foO(0), type(0));
    override(\Ns\MyClass::Bar(0), elementType(0));
    override(\foo(0), type(0));
    override(\bar(0), elementType(0));
}
