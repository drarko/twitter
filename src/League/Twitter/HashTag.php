<?php namespace League\Twitter;

class HashTag extends ObjectTwitterAbstract
{
    protected $text;

    /**
     * A class representing a Twitter HashTag.
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
}
