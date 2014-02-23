<?php namespace League\Twitter;

/**
 * A class representing a trending topic
 */
class Trend extends ObjectTwitterAbstract
{
    protected $name;
    protected $query;
    protected $timestamp;
    protected $url;


    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->query = isset($data['query']) ? $data['query'] : null;
        $this->timestamp = isset($data['timestamp']) ? $data['timestamp'] : null;
        $this->url = isset($data['url']) ? $data['url'] : null;
    }

    /**
     * @param null|string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null|string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return null|string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param int|null $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return int|null
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param null|string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return null|string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
