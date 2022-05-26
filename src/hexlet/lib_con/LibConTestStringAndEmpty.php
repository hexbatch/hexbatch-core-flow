<?php
namespace app\hexlet\lib_con;
/** @noinspection PhpUnused */

class LibConTestStringAndEmpty {
    public mixed  $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }

    public function __toString()
    {
        return $this->foo;
    }

    public function __isset($name)
    {
        return false;
    }
}