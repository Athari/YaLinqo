<?php

namespace YaLinqo;
use YaLinqo;

spl_autoload_register(function($class)
{
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file))
        require_once($file);
});

class Enumerable
{
    private $enumerator;

    public function __construct ($enumerator)
    {
        $this->enumerator = $enumerator;
    }

    /**
     * @return \YaLinqo\Enumerator
     */
    public function getEnumerator ()
    {
        return call_user_func($this->enumerator);
    }

    /**
     * @param mixed $obj
     * @throws \Exception
     * @return \YaLinqo\Enumerable
     */
    public static function from ($obj)
    {
        if (is_array($obj)) {
            return new Enumerable(function () use ($obj)
            {
                $i = 0;
                return new Enumerator(function ($yield) use (&$i, $obj)
                {
                    return $i < count($obj) ? $yield($obj[$i++]) : false;
                });
            });
        }
        throw new \Exception;
    }

    /**
     * @param $selector Closure|array|string
     * @return \YaLinqo\Enumerable
     */
    public function select ($selector)
    {
        $self = $this;
        $selector = Utils::createLambda($selector, Functions::$identity);
        return new Enumerable(function () use ($self, $selector)
        {
            $enum = null;
            $i = 0;
            return new Enumerator(
                function ($yield) use ($selector, &$enum, &$i)
                {
                    return $enum->moveNext() ? $yield(call_user_func($selector, $enum->current(), $i++)) : false;
                },
                function () use ($self, &$enum)
                {
                    /** @var $self Enumerable */
                    $enum = $self->getEnumerator();
                });
        });
    }
}

$enum = Enumerable::from(array('a', 'b', 'c', 1, 2, 3))
        ->select(function($i)
{ return "value: $i"; })
        ->getEnumerator();
while ($enum->moveNext())
    var_dump($enum->current());
