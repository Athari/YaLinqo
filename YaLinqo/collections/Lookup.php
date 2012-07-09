<?php

namespace YaLinqo\collections;
use YaLinqo\collections, YaLinqo\exceptions as e;

class Lookup extends Dictionary
{
    /** {@inheritdoc} */
    public function offsetGet ($offset)
    {
        if ($this->containsObjects) {
            $key = is_object($offset) ? spl_object_hash($offset) : $offset;
            return isset($this->data[$key]) ? $this->data[$key][1] : array();
        }
        else {
            return isset($this->data[$offset]) ? $this->data[$offset] : array();
        }
    }

    /**
     * TODODOC
     * @param mixed $offset
     * @param mixed $value
     */
    public function append ($offset, $value)
    {
        $offset = $this->convertOffset($offset);
        if (isset($this->data[$offset]))
            $this->data[$offset][] = $value;
        else
            $this->data[$offset] = array($value);
    }
}
