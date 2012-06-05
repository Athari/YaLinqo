<?php

namespace YaLinqo\collections;
use YaLinqo\collections, YaLinqo\exceptions as e;

class Lookup extends Dictionary
{
    public function append ($offset, $value)
    {
        $offset = $this->convertOffset($offset);
        if (isset($this->data[$offset]))
            $this->data[$offset][] = $value;
        else
            $this->data[$offset] = array($value);
    }
}
