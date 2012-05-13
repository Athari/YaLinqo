<?php

namespace YaLinqo;
use YaLinqo;
use YaLinqo\exceptions as e;

spl_autoload_register(function($class)
{
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file))
        require_once($file);
});

class Enumerable implements \IteratorAggregate
{
    private $iterator;

    public function __construct ($iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @return \YaLinqo\Enumerator
     */
    public function getIterator ()
    {
        $it = call_user_func($this->iterator);
        $it->rewind();
        return $it;
    }

    /**
     * @param mixed $obj
     * @throws \Exception
     * @return \YaLinqo\Enumerable
     */
    public static function from ($obj)
    {
        if ($obj instanceof \Iterator) {
            return new Enumerable(function () use ($obj)
            {
                return $obj;
            });
        }
        if (is_array($obj)) {
            return new Enumerable(function () use ($obj)
            {
                return new \ArrayIterator($obj);
            });
        }
        throw new \Exception;
    }

    /**
     * <p>select (selector {{value => result}) => enum
     * <p>select (selector {{value, key => result}) => enum
     * @param Closure|array|string $selector {value => result} | {value, key => result}
     * @return \YaLinqo\Enumerable
     */
    public function select ($selector)
    {
        $self = $this;
        $selector = Utils::createLambda($selector, Functions::$identity);

        return new Enumerable(function () use ($self, $selector)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            return new Enumerator(
                function ($yield) use ($it, $selector)
                {
                    /** @var $it \Iterator */
                    if (!$it->valid())
                        return false;
                    $yield(call_user_func($selector, $it->current(), $it->key()), $it->key());
                    $it->next();
                    return true;
                });
        });
    }

    /**
     * <p>where (predicate {{value => result}) => enum
     * <p>where (predicate {{value, key => result}) => enum
     * @param Closure|array|string $predicate {value => result} | {value, key => result}
     * @return \YaLinqo\Enumerable
     */
    public function where ($predicate)
    {
        $self = $this;
        $predicate = Utils::createLambda($predicate);

        return new Enumerable(function () use ($self, $predicate)
        {
            /** @var $self Enumerable */
            $it = $self->getIterator();
            return new Enumerator(
                function ($yield) use ($it, $predicate)
                {
                    /** @var $it \Iterator */
                    if (!$it->valid())
                        return false;
                    do {
                        if (call_user_func($predicate, $it->current(), $it->key())) {
                            $yield($it->current(), $it->key());
                            $it->next();
                            return true;
                        }
                        $it->next();
                    } while ($it->valid());
                    return false;
                });
        });
    }

    /**
     * <p>aggregate (func {{accum, value => result}, seed) => result
     * <p>aggregate (func {{accum, value, key => result}, seed) => result
     * @param Closure|array|string $func
     * @param $seed mixed
     */
    public function aggregate ($func, $seed = null)
    {
        $func = Utils::createLambda($func);
        $result = $seed;

        foreach ($this as $k => $v)
            $result = call_user_func($func, $result, $v, $k);
        return $result;
    }
}

$enum = Enumerable::from(array('a', 'b', 'c', 1, 'a' => 2, '100' => 3))
        ->where(
    function($v, $k)
    { return is_numeric($k); })
        ->select(
    function($v, $k)
    { return "$k: $v"; });

foreach ($enum as $k => $v)
    echo "($k): ($v)\n";

var_dump($enum->aggregate(function($a, $b)
{ return $a . $b; }));
