<?php

namespace Saffyre;

/**
 * This is a simple key-value collection class that allows case-insensitive keys.
 * It is used as the `headers` and `responseHeaders` properties of the `Controller` class.
 *
 * @see \Saffyre\Controller
 * @package Saffyre
 */
class HttpHeaders {

    private $values = [];

    public function __construct() {
    }

    public function __isset($name)
    {
        return isset($this->values[strtolower($name)]);
    }

    public function __get($name)
    {
        return isset($this->values[strtolower($name)]) ? $this->values[strtolower($name)] : null;
    }

    public function __set($name, $value)
    {
        $this->values[strtolower($name)] = $value;
    }
}