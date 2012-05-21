<?php

namespace YaLinqo;
use YaLinqo;

class Enumerator implements \Iterator
{
    const STATE_BEFORE = 0;
    const STATE_RUNNING = 1;
    const STATE_AFTER = 2;

    /** @var Closure */
    private $funNext;
    /** @var Closure */
    private $funYield;
    private $state = self::STATE_RUNNING;
    private $valid = true;
    public $currentValue = null;
    public $currentKey = null;

    /**
     * @param Closure $funNext
     */
    public function __construct ($funNext)
    {
        $this->funNext = $funNext;
        $self = $this;
        $this->funYield = function ($value, $key) use ($self)
        {
            /** @var $self Enumerator */
            $self->currentValue = $value;
            $self->currentKey = $key;
            return true;
        };
        $this->next();
    }

    /**
     * Move forward to next element.
     * @link http://php.net/manual/en/iterator.next.php
     * @throws \Exception
     * @return void Any returned value is ignored.
     */
    public function next ()
    {
        try {
            if ($this->state == self::STATE_RUNNING) {
                if (call_user_func($this->funNext, $this->funYield)) {
                    $this->valid = true;
                }
                else {
                    $this->state = self::STATE_AFTER;
                    $this->valid = false;
                }
            }
            elseif ($this->state == self::STATE_AFTER) {
                $this->valid = false;
            }
        }
        catch (\Exception $e) {
            $this->state = self::STATE_AFTER;
            throw $e;
        }
    }

    /**
     * Return the current element.
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current ()
    {
        return $this->currentValue;
    }

    /**
     * Return the key of the current element.
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key ()
    {
        return $this->currentKey;
    }

    /**
     * Checks if current position is valid.
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid ()
    {
        return $this->valid;
    }

    /**
     * Rewind the Iterator to the first element.
     * @link http://php.net/manual/en/iterator.rewind.php
     * @throws \YaLinqo\exceptions\NotSupportedException
     * @return void Any returned value is ignored.
     */
    public function rewind ()
    {
        //throw new e\NotSupportedException;
    }
}
