<?php

namespace League\Twitter;

class ObjectTwitterAbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectTwitterAbstract
     */
    private $objectTwitterAbstract;

    public function setUp()
    {
        $this->objectTwitterAbstract = new HashTag('#twitter');
    }

    public function testCanCreateObjectTwitterAbstract()
    {
        $this->assertInstanceOf('League\Twitter\ObjectTwitterAbstract', $this->objectTwitterAbstract);
    }

    public function testObjectToString()
    {
        $objectToString = $this->objectTwitterAbstract->__toString();
        $this->assertEquals('{"text":"#twitter"}', $objectToString);
    }

    public function testObjectToArray()
    {
        $objectToArray = $this->objectTwitterAbstract->toArray();
        $this->assertEquals(json_decode('{"text":"#twitter"}', true), $objectToArray);

    }

    public function testObjectToJson()
    {
        $objectToJson = $this->objectTwitterAbstract->toJson();
        $this->assertEquals('{"text":"#twitter"}', $objectToJson);
    }

    public function testObjectIsEqual()
    {
        $obj2 = clone $this->objectTwitterAbstract;
        $this->assertTrue($this->objectTwitterAbstract->isEqual($obj2));
    }
}
