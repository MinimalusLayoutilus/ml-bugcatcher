<?php

namespace mnhcc\ml\tests\Unit;

use PHPUnit\Framework\TestCase;
use mnhcc\ml\classes\Exception\InvalidArgumentException;

class ExceptionTest extends TestCase
{
    public function testInvalidArgumentException_extendsPhpInvalidArgumentException()
    {
        $e = new InvalidArgumentException('custom message');
        $this->assertInstanceOf('\InvalidArgumentException', $e);
    }

    public function testInvalidArgumentException_withTypeConstant_setsType()
    {
        $e = new InvalidArgumentException(InvalidArgumentException::TYPE_ARRAY);
        $this->assertSame(InvalidArgumentException::TYPE_ARRAY, $e->getType());
    }

    public function testInvalidArgumentException_withTypeConstant_generatesMessage()
    {
        $e = new InvalidArgumentException(InvalidArgumentException::TYPE_STRING);
        $this->assertNotEmpty($e->getMessage());
    }

    public function testInvalidArgumentException_withCustomMessage_preservesIt()
    {
        $e = new InvalidArgumentException('something went wrong');
        $this->assertSame('something went wrong', $e->getMessage());
    }

    public function testTypeConstants_areDefinedWithExpectedValues()
    {
        $this->assertSame('array',   InvalidArgumentException::TYPE_ARRAY);
        $this->assertSame('boolean', InvalidArgumentException::TYPE_BOOLEAN);
        $this->assertSame('integer', InvalidArgumentException::TYPE_INTEGER);
        $this->assertSame('string',  InvalidArgumentException::TYPE_STRING);
        $this->assertNull(InvalidArgumentException::TYPE_NULL);
    }

    public function testInvalidArgumentException_isThrowable()
    {
        $this->expectException('\InvalidArgumentException');
        throw new InvalidArgumentException('test throw');
    }
}
