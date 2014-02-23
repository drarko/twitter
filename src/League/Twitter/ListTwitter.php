<?php namespace League\Twitter;

/**
 * A class representing the List structure used by the twitter API.
 */
class ListTwitter extends ObjectTwitterAbstract
{
    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->slug = isset($data['slug']) ? $data['slug'] : null;
        $this->description = isset($data['description']) ? $data['description'] : null;
        $this->full_name = isset($data['full_name']) ? $data['full_name'] : null;
        $this->mode = isset($data['mode']) ? $data['mode'] : null;
        $this->uri = isset($data['uri']) ? $data['uri'] : null;
        $this->member_count = isset($data['member_count']) ? $data['member_count'] : null;
        $this->subscriber_count = isset($data['subscriber_count']) ? $data['subscriber_count'] : null;
        $this->following = isset($data['following']) ? $data['following'] : null;
        $this->user = isset($data['user']) ? $data['user'] : null;
    }

    /**
     * Get the unique id of this list.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the unique id of this list.
     */
    public function setId($id)
    {
        return $this->id = $id;
    }

    /**
     * Get the real name of this list.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the real name of this list.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the slug of this list.
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set the slug of this list.
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get the description of this list.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description of this list.
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get the full_name of this list.
     */
    public function getFullName()
    {
        return $this->full_name;
    }

    /**
     * Set the full_name of this list.
     */
    public function setFullName($full_name)
    {
        $this->full_name = $full_name;
    }

    /**
     * Get the mode of this list.
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set the mode of this list.
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get the uri of this list.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the uri of this list.
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get the member_count of this list.
     */
    public function getMemberCount()
    {
        return $this->member_count;
    }

    /**
     * Set the member_count of this list.
     */
    public function setMemberCount($member_count)
    {
        $this->member_count = $member_count;
    }

    /**
     * Get the subscriber_count of this list.
     */
    public function getSubscriberCount()
    {
        return $this->subscriber_count;
    }

    /**
     * Set the subscriber_count of this list.
     */
    public function setSubscriberCount($subscriber_count)
    {
        $this->subscriber_count = $subscriber_count;
    }

    /**
     * Get the following status of this list.
     */
    public function getFollowing()
    {
        return $this->following;
    }

    /**
     * Set the following status of this list.
     */
    public function setFollowing($following)
    {
        $this->following = $following;
    }

    /**
     * Get the user of this list.
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user of this list.
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
}
