<?php

class extractMethod2
{
    const HELLO_WORLD = 1234;

    public function bigMethod()
    {
        echo self::HELLO_WORLD;
    }
}
