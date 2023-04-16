<?php

namespace Impack\WP\Base;

use Closure;
use Impack\WP\Base\SingletonTrait;

trait ContainerTrait
{
    use SingletonTrait;

    protected $instances = [];

    protected $bindings = [];

    /**
     * 绑定WP菜单页面并添加 `load-{$hookSuffix}` 钩子
     *
     * @param string $hookSuffix
     * @param string $concrete
     */
    public function bindAdminPage($hookSuffix, $concrete)
    {
        $this->singleton($hookSuffix, $concrete);

        \add_action('load-' . $hookSuffix, fn() => $this->make($hookSuffix)->load());
    }

    /**
     * 判断指定ID是否已绑定
     *
     * @param  string $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * 添加单例
     *
     * @param  string  $abstract
     * @param  mixed   $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * 注册单例
     *
     * @param string               $abstract
     * @param \Closure|string|null $concrete
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = $concrete ? $concrete : $abstract;
    }

    /**
     * 获取实例
     *
     * @param  string  $abstract
     * @param  mixed   ...$params
     * @return mixed
     */
    public function make($abstract, ...$params)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = isset($this->bindings[$abstract]) ? $this->bindings[$abstract] : $abstract;
        $object   = $concrete instanceof \Closure ? $concrete(...$params) : new $concrete(...$params);

        if (method_exists($object, 'setApp')) {
            $object->setApp($this);
        }

        $this->instances[$abstract] = $object;

        return $object;
    }

    /**
     * 移除已解析的单例
     *
     * @param string $abstract
     */
    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    // public function wrap(Closure $callback, ...$params)
    // {
    //     return function () use ($callback, $params) {
    //         return $this->call($callback, ...$params);
    //     };
    // }
}