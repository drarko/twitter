<?php namespace League\Twitter;

/**
 * A class representing a URL contained in a tweet
 */
class Url extends ObjectTwitterAbstract
{
    protected $url;

    protected $expanded_url;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->url = isset($data['url']) ? $data['url'] : null;
        $this->expanded_url = isset($data['expanded_url']) ? $data['expanded_url'] : null;
    }

    /**
     * @param string $expanded_url
     */
    public function setExpandedUrl($expanded_url)
    {
        $this->expanded_url = $expanded_url;
    }

    /**
     * @return string
     */
    public function getExpandedUrl()
    {
        return $this->expanded_url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
