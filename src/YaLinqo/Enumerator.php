<?php

namespace YaLinqo;
use YaLinqo;

class Enumerator implements \Iterator
{
    const STATE_BEFORE = 0;
    const STATE_RUNNING = 1;
    const STATE_AFTER = 2;

    /** @var \Closure */
    private $funNext;
    /** @var \Closure */
    private $funYield;
    private $state = self::STATE_RUNNING;
    private $valid = true;
    public $currentValue = null;
    public $currentKey = null;

    /**
     * @param \Closure $funNext
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

    /** {@inheritdoc} */
    public function next ()
    {
        try {
            if ($this->state == self::STATE_RUNNING) {
                if (call_user_func($this->funNext, $this->funYield)) {
                    $this->valid = true;
                } else {
                    $this->state = self::STATE_AFTER;
                    $this->valid = false;
                }
            } elseif ($this->state == self::STATE_AFTER) {
                $this->valid = false;
            }
        } catch (\Exception $e) {
            $this->state = self::STATE_AFTER;
            throw $e;
        }
    }

    /** {@inheritdoc} */
    public function current () { return $this->currentValue; }

    /** {@inheritdoc} */
    public function key () { return $this->currentKey; }

    /** {@inheritdoc} */
    public function valid () { return $this->valid; }

    /** {@inheritdoc} */
    public function rewind () { }
}
