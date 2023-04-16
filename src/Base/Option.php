<?php

namespace Impack\WP\Base;

use Impack\WP\Manager\OptionSetting;
use WP_Error;

class Option
{
    protected $name;

    protected $group;

    protected $app;

    protected $defaultValue;

    protected $value;

    public function __construct($name, $group, $app = null)
    {
        $this->name  = $name;
        $this->group = $group;
        $this->app   = $app;
    }

    /**
     * @param  array|mixed            $value
     * @return array|mixed|WP_Error
     */
    public function sanitize($value)
    {
        return $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function exists()
    {
        return \get_option($this->getName(), null) !== null;
    }

    public function add($value = '', $deprecated = '', $autoload = 'yes')
    {
        return \add_option($this->getName(), $value, $deprecated, $autoload);
    }

    public function get($key = '')
    {
        if (!isset($this->value)) {
            $this->value = \get_option($this->getName());

            if ($this->value === false) {
                $this->value = $this->getDefaultValue();
            }
        }

        if (!$key) {
            return $this->value;
        }

        $keys  = explode('.', $key);
        $array = $this->value;

        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return null;
            }
        }

        return $array;
    }

    public function update($value, $autoload = null)
    {
        if ($result = \update_option($this->getName(), $value, $autoload)) {
            $this->value = null;
        }

        return $result;
    }

    public function delete()
    {
        $this->value = null;
        return \delete_option($this->getName());
    }

    public function getDefaultValue()
    {
        if (isset($this->defaultValue)) {
            return $this->defaultValue;
        }

        if (isset($this->app)) {
            return $this->defaultValue = (new OptionSetting($this->app, $this->getName(), $this->group))->loadDefaultValue();
        }

        return $this->defaultValue = false;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
    }

    public function setApp($app)
    {
        $this->app = $app;
    }
}