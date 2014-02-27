<?php

namespace League\Twitter;

class UserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        $this->user = new User(array());
    }

    public function testCanCreateUser()
    {
        $this->assertInstanceOf('League\Twitter\User', $this->user);
    }

    public function testgetAndSetId()
    {
        $this->user->setId('123');
        $this->assertEquals('123', $this->user->getId());
    }

    public function testgetAndSetName()
    {
        $this->user->setName('thephpleague');
        $this->assertEquals('thephpleague', $this->user->getName());
    }

    public function testgetAndSetScreenName()
    {
        $this->user->setScreenName('@thephpleague');
        $this->assertEquals('@thephpleague', $this->user->getScreenName());
    }

    public function testgetAndSetLocation()
    {
        $this->user->setLocation('spain');
        $this->assertEquals('spain', $this->user->getLocation());
    }

    public function testgetAndSetDescription()
    {
        $this->user->setDescription('thephpleague');
        $this->assertEquals('thephpleague', $this->user->getDescription());
    }

    public function testgetAndSetProfileImageUrl()
    {
        $this->user->setProfileImageUrl('http://www.google.com');
        $this->assertEquals('http://www.google.com', $this->user->getProfileImageUrl());
    }

    public function testgetAndSetProfileBackgroundTile()
    {
        $this->user->setProfileBackgroundTile('thephpleague');
        $this->assertEquals('thephpleague', $this->user->getProfileBackgroundTile());
    }

    public function testgetAndSetProfileBackgroundImageUrl()
    {
        $this->user->setProfileBackgroundImageUrl('http://www.google.com');
        $this->assertEquals('http://www.google.com', $this->user->getProfileBackgroundImageUrl());
    }

    public function testgetAndSetProfileSidebarFilColor()
    {
        $this->user->setProfileSidebarFillColor('#FFFFFF');
        $this->assertEquals('#FFFFFF', $this->user->getProfileSidebarFillColor());
    }


    public function testgetAndSetProfileBackgroundColor()
    {
        $this->user->setProfileBackgroundColor('#FFFFFF');
        $this->assertEquals('#FFFFFF', $this->user->getProfileBackgroundColor());
    }

    public function testgetAndSetProfileLinkColor()
    {
        $this->user->setProfileLinkColor('#FFFFFF');
        $this->assertEquals('#FFFFFF', $this->user->getProfileLinkColor());
    }

    public function testgetAndSetProfileTextColor()
    {
        $this->user->setProfileTextColor('#FFFFFF');
        $this->assertEquals('#FFFFFF', $this->user->getProfileTextColor());
    }

    public function testgetAndSetProtected()
    {
        $this->user->setProtected(true);
        $this->assertEquals(true, $this->user->getProtected());
    }

    public function testgetAndSetUtcOffset()
    {
        $this->user->setUtcOffset(true);
        $this->assertEquals(true, $this->user->getUtcOffset());
    }

    public function testgetAndSetTimeZone()
    {
        $this->user->setTimeZone('GMT+1');
        $this->assertEquals('GMT+1', $this->user->getTimeZone());
    }

    public function testGetAndSetFollowersCount()
    {
        $this->user->setFollowersCount(42);
        $this->assertEquals(42, $this->user->getFollowersCount());
    }

    public function testGetAndSetFriendsCount()
    {
        $this->user->setFriendsCount(42);
        $this->assertEquals(42, $this->user->getFriendsCount());
    }

    public function testGetAndSetStatusesCount()
    {
        $this->user->setStatusesCount(42);
        $this->assertEquals(42, $this->user->getStatusesCount());
    }

    public function testGetAndSetFavouritesCount()
    {
        $this->user->setFavouritesCount(42);
        $this->assertEquals(42, $this->user->getFavouritesCount());
    }

    public function testGetAndSetUrl()
    {
        $this->user->setUrl('http://www.google.com');
        $this->assertEquals('http://www.google.com', $this->user->getUrl());
    }

    public function testGetAndSetStatus()
    {
        $this->user->setStatus(new Status(array()));
        $this->assertInstanceOf('League\Twitter\Status', $this->user->getStatus());
    }

    public function testGetAndSetGeoEnabled()
    {
        $this->user->setGeoEnabled(true);
        $this->assertEquals(true, $this->user->getGeoEnabled());
    }

    public function testGetAndSetVerified()
    {
        $this->user->setVerified(false);
        $this->assertEquals(false, $this->user->getVerified());
    }

    public function testGetAndSetLang()
    {
        $this->user->setLang('en');
        $this->assertEquals('en', $this->user->getLang());
    }

    public function testGetAndSetNotifications()
    {
        $this->user->setNotifications(true);
        $this->assertEquals(true, $this->user->getNotifications());
    }

    public function testGetAndSetContributorsEnabled()
    {
        $this->user->setContributorsEnabled(true);
        $this->assertEquals(true, $this->user->getContributorsEnabled());
    }

    public function testGetAndSetCreatedAt()
    {
        $now = time();
        $this->user->setCreatedAt($now);
        $this->assertEquals($now, $this->user->getCreatedAt());
    }

    public function testGetAndSetListedCount()
    {

        $this->user->setListedCount(42);
        $this->assertEquals(42, $this->user->getListedCount());
    }
}
