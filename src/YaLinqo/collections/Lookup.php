<?php

namespace YaLinqo\collections;
use YaLinqo\collections, YaLinqo\exceptions as e;

class Lookup extends Dictionary
{
    const ERROR_LOOKUP_VALUE_NOT_ARRAY = 'value must be array.';

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
        $key = $this->convertOffset($offset);
        if ($this->containsObjects) {
            if (isset($this->data[$key]))
                $this->data[$key][1][] = $value;
            else
                $this->data[$key] = array($offset, array($value));
        }
        else {
            if (isset($this->data[$key]))
                $this->data[$key][] = $value;
            else
                $this->data[$key] = array($value);
        }
    }

    /** {@inheritdoc} */
    public function offsetSet ($offset, $value)
    {
        if (!is_array($value))
            throw new \InvalidArgumentException(self::ERROR_LOOKUP_VALUE_NOT_ARRAY);
        parent::offsetSet($offset, $value);
    }
}
