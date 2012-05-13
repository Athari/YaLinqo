<?php

namespace YaLinqo;
use YaLinqo;

class Enumerator
{
    const STATE_BEFORE = 0;
    const STATE_RUNNING = 1;
    const STATE_AFTER = 2;

    /** @var Closure */
    private $funNext;
    /** @var Closure */
    private $funInit;
    /** @var Closure */
    private $funYield;
    private $state = self::STATE_BEFORE;
    public $current = null;

    public function __construct ($funNext, $funInit = null, $funStop = null)
    {
        $this->funNext = Utils::createLambda($funNext);
        $this->funInit = Utils::createLambda($funInit, Functions::$blank);
        $self = $this;
        $this->funYield = function ($value) use ($self)
        {
            $self->current = $value;
            return true;
        };
    }

    public function current ()
    {
        return $this->current;
    }

    public function moveNext ()
    {
        try {
            if ($this->state == self::STATE_BEFORE) {
                $this->state = self::STATE_RUNNING;
                call_user_func($this->funInit);
            }
            if ($this->state == self::STATE_RUNNING) {
                if (call_user_func($this->funNext, $this->funYield)) {
                    return true;
                }
                else {
                    $this->state = self::STATE_AFTER;
                    return false;
                }
            }
            if ($this->state == self::STATE_AFTER) {
                return false;
            }
        }
        catch (\Exception $e) {
            $this->state = self::STATE_AFTER;
            throw $e;
        }
        throw new \Exception;
    }
}
