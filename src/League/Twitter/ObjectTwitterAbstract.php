<?php

namespace League\Twitter;

abstract class ObjectTwitterAbstract
{
    /**
     * Comparison to see if the provided object equals the current instance
     * @param \League\Twitter\ObjectTwitterAbstract $other
     * @return bool
     */
    public function isEqual($other)
    {
        return ($this == $other);
    }

    /**
     * Method for printing the object as a string
     * @return string a json representation of the object
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Method for printing the object as a string
     * @return string a json representation of the object
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Method to return the User object as an array
     * @return array $data
     */
    public function toArray()
    {
        return array_filter(get_object_vars($this));
    }
}
