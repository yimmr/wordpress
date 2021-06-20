<?php

namespace Impack\WP\Service;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * @method string path(string $path = '')
 * @method string viewPath(string $path = '')
 * @method string configPath(string $path = '')
 */
abstract class Base
{
    protected static $instance;

    protected $prefix = 'imwp';

    protected $bindings = [];

    protected $instances = [];

    abstract public function provider();

    public function __construct()
    {
        static::$instance = $this;
    }

    /**
     * 给指定键名添加前缀
     *
     * @return string
     */
    public function prefix(string $key = '', string $delimiter = '_')
    {
        return $this->prefix . $delimiter . $key;
    }

    /**
     * 指定对象方法与动作钩子绑定
     *
     * @param Object|null $object
     * @param string $method
     * @param int $priority
     * @param int $acceptedArgs
     * @return true
     */
    public function bindAction($object, $method, $priority = 10, $acceptedArgs = 1)
    {
        return \add_action($this->methodToHook($method), [is_null($object) ? $this : $object, $method], $priority, $acceptedArgs);
    }

    /**
     * 指定对象方法与过滤钩子绑定
     *
     * @param Object|null $object
     * @param string $method
     * @param int $priority
     * @param int $acceptedArgs
     * @return true
     */
    public function bindFilter($object, $method, $priority = 10, $acceptedArgs = 1)
    {
        return \add_filter($this->methodToHook($method), [is_null($object) ? $this : $object, $method], $priority, $acceptedArgs);
    }

    /**
     * 方法名转成钩子名
     *
     * @param string $value
     * @return string
     */
    public function methodToHook($value)
    {
        return mb_strtolower(preg_replace('/.(?=[A-Z])/u', '$0_', $value), 'UTF-8');
    }

    /**
     * 注册单例
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = $concrete ? $concrete : $abstract;
    }

    /**
     * 解析单例
     *
     * @param string $abstract
     * @return mixed
     */
    public function make($abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $object = $this->build(isset($this->bindings[$abstract]) ? $this->bindings[$abstract] : $abstract);

        $this->instances[$abstract] = $object;

        return $object;
    }

    /**
     * 构建实例
     *
     * @param string|Closure $concrete
     * @return mixed
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete();
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ReflectionException("Target class [$concrete] does not exist.", 0, $e);
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $deps = [];

        foreach ($constructor->getParameters() as $param) {
            if (
                $param->getClass()
                && ($param->getClass()->name == static::class || $param->getClass()->name == self::class)
            ) {
                $deps[] = $this;
            } elseif ($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                throw new InvalidArgumentException("Unable to resolve dependency [{$param}]");
            }
        }

        return $reflector->newInstanceArgs($deps);
    }

    /**
     * 添加单例
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;

        return $instance;
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

    /**
     * make方法的静态用法
     *
     * @param string $abstract
     * @return mixed
     */
    public static function create($abstract)
    {
        return static::getInstance()->make($abstract);
    }

    /**
     * 设置服务单例
     *
     * @param \Impack\WP\Service\Base $instance
     * @return static
     */
    public static function setInstance(Base $instance)
    {
        static::$instance = $instance;

        return $instance;
    }

    /**
     * 返回服务单例
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * getInstance别名
     *
     * @return static
     */
    public static function i()
    {
        return static::$instance;
    }
}