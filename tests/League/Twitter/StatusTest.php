<?php

namespace League\Twitter;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Status
     */
    private $status;

    public function setUp()
    {
        $this->status = new Status(array());
    }

    public function testCanCreateStatus()
    {
        $this->assertInstanceOf('League\Twitter\Status', $this->status);
    }
}
