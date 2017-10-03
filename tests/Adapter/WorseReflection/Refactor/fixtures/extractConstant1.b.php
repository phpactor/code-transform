<?php

class extractMethod1
{
    const HELLO_WORLD = 'hello_world';

    public function bigMethod()
    {
        echo self::HELLO_WORLD;
    }
}
