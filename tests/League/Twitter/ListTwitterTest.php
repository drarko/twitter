<?php

namespace League\Twitter;

class ListTwitterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ListTwitter
     */
    private $list;

    public function setUp()
    {
        $this->list = new ListTwitter(array());
    }

    public function testCanCreateList()
    {
        $this->assertInstanceOf('League\Twitter\ListTwitter', $this->list);
    }
}
