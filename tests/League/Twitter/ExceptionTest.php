<?php

namespace League\Twitter;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Exception
     */
    private $exception;

    public function setUp()
    {
        $this->exception = new Exception();
    }

    public function testCanCreateHashTag()
    {
        $this->assertInstanceOf('League\Twitter\Exception', $this->exception);
    }
}
