<?php

namespace Tests\Unit;

use A2Workspace\SocialEntry\Contracts\DataAccess;
use A2Workspace\SocialEntry\Concerns\DataAccessTrait;
use InvalidArgumentException;

class DataAccessTest extends TestCase
{
    public function test_parse()
    {
        $payload = DataAccessStub::parse([
            'foo' => 'bar',
        ]);

        $this->assertEquals('bar', $payload->foo);
    }

    public function test_parse_invalid_value()
    {
        $this->expectException(InvalidArgumentException::class);

        DataAccessStub::parse('INVALID_VALUE');
    }

    public function test_array_accessable()
    {
        $payload = new DataAccessStub;
        $payload->foo = 'bar';

        $this->assertTrue(isset($payload['foo']));
        $this->assertEquals('bar', $payload['foo']);

        $payload['foo'] = '2000';
        $this->assertEquals('2000', $payload['foo']);

        unset($payload['foo']);
        $this->assertTrue(empty($payload['foo']));
    }

    public function test_should_throw_exception_when_getting_undefined_property()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined property: Tests\Unit\DataAccessStub::$something');

        (new DataAccessStub)->something;
    }

    public function test_should_throw_exception_when_setting_undefined_property()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined property: Tests\Unit\DataAccessStub::$something');

        $payload = new DataAccessStub;
        $payload->something = 'else';
    }
}

class DataAccessStub implements DataAccess
{
    use DataAccessTrait;

    public $foo;
}
