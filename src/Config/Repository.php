<?php

namespace Impack\WP\Config;

use ArrayAccess;
use Closure;
use Impack\WP\Support\Arr;

class Repository implements ArrayAccess
{
    protected $app;

    protected $items = [];

    protected $keysegs = [];

    protected $loaders = [
        'file'   => \Impack\WP\Config\FileLoader::class,
        'option' => \Impack\WP\Config\OptionLoader::class,
    ];

    public function __construct($app = null)
    {
        if (!is_null($app)) {
            $this->app = $app;
        }
    }

    /**
     * 是否存在配置项
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items($key), $key);
    }

    /**
     * 返回指定配置值
     *
     * @param  string|array  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return !is_array($key) ? Arr::get($this->items($key), $key, $default) : $this->getMany($key);
    }

    /**
     * 设置配置项
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $sync
     */
    public function set($key, $value = '', $sync = true)
    {
        $this->items($key);
        Arr::set($this->items, $key, $value);

        if ($sync) {
            $this->syncToLoader($key, 'update');
        }
    }

    /**
     * 移除配置项
     *
     * @param  string  $key
     */
    public function forget($key)
    {
        $this->items($key);
        Arr::forget($this->items, $key);
        $this->syncToLoader($key, 'delete');
    }

    /**
     * 返回多组配置值
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items($key), $key, $default);
        }

        return $config;
    }

    /**
     * 指定配置值的前面添加值
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function prepend($key, $value)
    {
        if (is_array($array = $this->get($key))) {
            array_unshift($array, $value);
            $this->set($key, $array);
        }
    }

    /**
     * 指定配置值的后面添加值
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function push($key, $value)
    {
        if (is_array($array = $this->get($key))) {
            $array[] = $value;
            $this->set($key, $array);
        }
    }

    /**
     * 读取配置项到缓存区并返回当前缓存
     *
     * @param  string  $key
     * @return array
     */
    public function items($key = '')
    {
        if (empty($key) || Arr::has($this->items, $key)) {
            return $this->items;
        }

        $keyseg = $this->keyseg($key);

        $this->getLoader($keyseg[0])->load($keyseg, $this->items);

        return $this->items;
    }

    /**
     * 添加或设置指定项的加载器
     *
     * @param  string  $name
     * @param  Closure|\Impack\WP\Config\LoaderContract|sting  $loader 类名/实例/闭包返回实现约定的实例
     */
    public function loader($name, $loader)
    {
        $this->loaders[$name] = $loader;
    }

    /**
     * 获取配置项对应的加载器
     *
     * @param  string  $name
     * @return \Impack\WP\Config\LoaderContract
     */
    protected function getLoader($name = 'file')
    {
        $name   = isset($this->loaders[$name]) ? $name : 'file';
        $loader = $this->loaders[$name];

        if (is_string($loader)) {
            return $this->loaders[$name] = new $loader($this->app);
        } elseif ($loader instanceof Closure) {
            return $this->loaders[$name] = $loader($this->app);
        }

        return $loader;
    }

    /**
     * 缓存区操作同步至加载器
     *
     * @param  string  $key
     * @param  string  $method
     */
    protected function syncToLoader($key, $method = 'update')
    {
        $keyseg = $this->keyseg($key);

        $this->getLoader($keyseg[0])->{$method}($keyseg, $this->items);
    }

    /**
     * 点分隔的字符拆成数组
     *
     * @param  string  $key
     * @return array
     */
    protected function keyseg(string $key)
    {
        return $this->keysegs[$key] ?? $this->keysegs[$key] = explode('.', $key);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->forget($offset);
    }
}