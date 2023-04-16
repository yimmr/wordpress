<?php

namespace Impack\WP\Base;

use ArrayAccess;

class Config implements ArrayAccess
{
    protected $configPath;

    protected $items = [];

    protected $loaded = [];

    public function __construct($configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * 是否存在配置项
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        if (!$this->items || !$key) {
            return false;
        }

        $keys = explode('.', $key);
        $this->loadConfig(current($keys));
        $array = $this->items;

        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 读取配置值
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $this->loadConfig(current($keys));
        $array = $this->items;

        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * 设置配置项
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value = null)
    {
        if (is_null($key)) {
            $this->items = $value;
            return;
        }

        $keys  = explode('.', $key);
        $array = &$this->items;

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * 移除单个或一组配置项
     *
     * @param string|string[] $keys
     */
    public function forget($keys)
    {
        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->items)) {
                unset($this->items[$key]);
                continue;
            }

            $parts = explode('.', $key);
            $array = &$this->items;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * 加载配置文件
     *
     * @param string $name   不含扩展名的文件名
     * @param bool   $reload 是否重新加载配置文件
     */
    public function loadConfig($name, $reload = false)
    {
        if ($reload || !in_array($name, $this->loaded)) {
            $this->items[$name] = include_once $this->configPath . \DIRECTORY_SEPARATOR . $name . '.php';
            $this->loaded[]     = $name;
        }
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->forget($offset);
    }
}