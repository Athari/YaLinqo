<?php

namespace YaLinqo;

class Linq
{
    public static function from($source)
    {
        return Enumerable::from($source);
    }
}
