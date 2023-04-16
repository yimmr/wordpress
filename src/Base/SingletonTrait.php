<?php

namespace Impack\WP\Base;

trait SingletonTrait
{
    protected static $instance;

    public static function setInstance($instance)
    {
        static::$instance = $instance;
    }

    public static function getInstance()
    {
        return static::$instance ?? static::$instance = new static;
    }

    public static function i()
    {
        return static::$instance;
    }
}