<?php

namespace CodeInspector\Tests;

use CodeInspector\Type;
use CodeInspector\TypeChecker;
use PHPUnit_Framework_TestCase;

class TypeReconciliationTest extends PHPUnit_Framework_TestCase
{
    public function testNotNull()
    {
        $this->assertEquals(
            'Object',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('Object'))
        );

        $this->assertEquals(
            'Object',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('Object|null'))
        );

        $this->assertEquals(
            'Object|false',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('Object|false'))
        );

        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('mixed'))
        );
    }

    public function testNotEmpty()
    {
        $this->assertEquals(
            'Object',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('Object'))
        );

        $this->assertEquals(
            'Object',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('Object|null'))
        );

        $this->assertEquals(
            'Object',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('Object|false'))
        );

        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('mixed'))
        );

        $this->markTestIncomplete('This should work in the future');

        $this->assertEquals(
            'Object|true',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('Object|bool'))
        );
    }

    public function testNull()
    {
        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('null', Type::parseString('Object|null'))
        );

        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('null', Type::parseString('Object'))
        );

        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('null', Type::parseString('Object|false'))
        );

        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('null', Type::parseString('mixed'))
        );
    }

    public function testEmpty()
    {
        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('Object'))
        );
        $this->assertEquals(
            'false',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('Object|false'))
        );

        $this->assertEquals(
            'false',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('Object|bool'))
        );

        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('mixed'))
        );

        $reconciled = TypeChecker::reconcileTypes('empty', Type::parseString('bool'));
        $this->assertEquals('false', (string) $reconciled);
        $this->assertInstanceOf('CodeInspector\Type\Atomic', $reconciled->types['false']);
    }

    public function testNotObject()
    {
        $this->assertEquals(
            'bool',
            (string) TypeChecker::reconcileTypes('!Object', Type::parseString('Object|bool'))
        );

        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('!Object', Type::parseString('Object|null'))
        );

        $this->assertEquals(
            'ObjectB',
            (string) TypeChecker::reconcileTypes('!ObjectA', Type::parseString('ObjectA|ObjectB'))
        );
    }

    public function testObject()
    {
        $this->assertEquals(
            'Object',
            (string) TypeChecker::reconcileTypes('Object', Type::parseString('Object|bool'))
        );

        $this->assertEquals(
            'ObjectA',
            (string) TypeChecker::reconcileTypes('ObjectA', Type::parseString('ObjectA|ObjectB'))
        );
    }

    public function testAllMixed()
    {
        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('mixed', Type::parseString('mixed'))
        );
    }
}
