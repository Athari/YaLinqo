<?php

namespace YaLinqo\collections;
use YaLinqo\collections, YaLinqo\exceptions as e;

class Dictionary implements \Iterator, \ArrayAccess, \Countable
{
    const ERROR_ARRAY_KEYS = 'Array keys are not supported.';

    /** @var array */
    protected $data = array();
    /** @var bool */
    protected $containsObjects = false;
    /** @var bool */
    private $current = false;

    /** {@inheritdoc} */
    public function current ()
    {
        return $this->containsObjects ? $this->current[1] : $this->current;
    }

    /** {@inheritdoc} */
    public function next ()
    {
        $this->current = next($this->data);
    }

    /** {@inheritdoc} */
    public function key ()
    {
        return $this->containsObjects ? $this->current[0] : key($this->data);
    }

    /** {@inheritdoc} */
    public function valid ()
    {
        return key($this->data) !== null;
    }

    /** {@inheritdoc} */
    public function rewind ()
    {
        reset($this->data);
        $this->current = current($this->data);
    }

    /** {@inheritdoc} */
    public function offsetExists ($offset)
    {
        return $this->containsObjects
                ? isset($this->data[spl_object_hash($offset)])
                : isset($this->data[$offset]);
    }

    /** {@inheritdoc} */
    public function offsetGet ($offset)
    {
        return $this->containsObjects
                ? $this->data[spl_object_hash($offset)][1]
                : $this->data[$offset];
    }

    /** {@inheritdoc} */
    public function offsetSet ($offset, $value)
    {
        $key = $this->convertOffset($offset);
        if ($this->containsObjects)
            $this->data[$key] = array($offset, $value);
        else
            $this->data[$key] = $value;
    }

    /** {@inheritdoc} */
    public function offsetUnset ($offset)
    {
        if ($this->containsObjects)
            unset($this->data[is_object($offset) ? spl_object_hash($offset) : $offset]);
        else
            unset($this->data[$offset]);
    }

    /** {@inheritdoc} */
    public function count ()
    {
        return count($this->data);
    }

    /**
     * TODODOC
     * @return array
     */
    public function toArray ()
    {
        return $this->data;
    }

    protected function convertOffset ($offset)
    {
        if (is_object($offset)) {
            $this->ensureContainsObjects();
            return spl_object_hash($offset);
        }
        elseif (is_array($offset))
            throw new e\NotSupportedException(self::ERROR_ARRAY_KEYS);
        return $offset;
    }

    protected function ensureContainsObjects ()
    {
        if ($this->containsObjects)
            return;

        $this->containsObjects = true;
        foreach ($this->data as $k => $v)
            $this->data[$k] = array($k, $v);
    }
}
