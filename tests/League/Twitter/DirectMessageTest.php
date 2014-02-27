<?php

namespace League\Twitter;

class DirectMessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DirectMessage
     */
    private $direct_message;

    public function setUp()
    {
        $this->direct_message = new DirectMessage(array());
    }

    public function testCanCreateDirectMessage()
    {
        $this->assertInstanceOf('League\Twitter\DirectMessage', $this->direct_message);
    }

    public function testGetAndSetId()
    {
        $this->direct_message->setId(123);
        $this->assertEquals(123, $this->direct_message->getId());
    }

    public function testGetAndSetText()
    {
        $this->direct_message->setText('thephpleague');
        $this->assertEquals('thephpleague', $this->direct_message->getText());
    }

    public function testGetAndSetCreatedAt()
    {
        $now = new \DateTime();
        $this->direct_message->setCreatedAt($now);
        $this->assertEquals($now, $this->direct_message->getCreatedAt());
    }

    public function testGetAndSetRecipientId()
    {
        $this->direct_message->setRecipientId(123);
        $this->assertEquals(123, $this->direct_message->getRecipientId());
    }

    public function testGetAndSetSenderId()
    {
        $this->direct_message->setSenderId(123);
        $this->assertEquals(123, $this->direct_message->getSenderId());
    }

    public function testGetAndSetSenderScreenName()
    {
        $this->direct_message->setSenderScreenName('@thephpleague');
        $this->assertEquals('@thephpleague', $this->direct_message->getSenderScreenName());
    }

    public function testGetAndSetRecipientScreenName()
    {
        $this->direct_message->setRecipientScreenName('@thephpleague');
        $this->assertEquals('@thephpleague', $this->direct_message->getRecipientScreenName());
    }

    public function testGetCreatedAtInSeconds()
    {
        $this->direct_message->setCreatedAt(new \DateTime());
        $seconds = $this->direct_message->getCreatedAtInSeconds();
        $this->assertLessThan(5, $seconds);
    }
}
