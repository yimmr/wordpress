<?php

namespace Impack\WP\Base;

use Impack\Container\Container;
use Impack\WP\Service\PathTrait;

abstract class Application extends Container
{
    use PathTrait;

    protected $prefix = 'imwp';

    protected $serviceTypes = [];

    protected $hasBeenBootstrapped = false;

    public function __construct($path = '', $URL = '')
    {
        $this->setPath($path)->setURL($URL);
        $this->registerBaseBindings();
        $this->registerCoreAliases();
    }

    /** 服务提供者 */
    abstract public function provider();

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

    /** 注册前台服务 */
    public function web($service)
    {
        $this->serviceTypes['web'] = $service;
    }

    /** 注册后台服务 */
    public function admin($service)
    {
        $this->serviceTypes['admin'] = $service;
    }

    /** 注册REST API服务 */
    public function rest($service)
    {
        $this->serviceTypes['rest'] = $service;
    }

    /** 注册admin-ajax服务 */
    public function ajax($service)
    {
        $this->serviceTypes['ajax'] = $service;
    }

    /**
     * 判断是否已开启调试
     *
     * @return bool
     */
    public function isDebugging()
    {
        return defined('WP_DEBUG') ? \WP_DEBUG : false;
    }

    /**
     * 开始加载WP时的时间(s)
     *
     * @return float
     */
    public static function timestart()
    {
        global $timestart;
        return $timestart;
    }

    /**
     * 启动应用
     *
     * @param array $bootstrappers
     */
    public function boot(array $bootstrappers = [])
    {
        $this->addCoreHooks();
        if (!$this->hasBeenBootstrapped()) {
            $this->provider();
            $this->bootstrapWith($bootstrappers);
        }
        $this->dispatchToService();
    }

    /** 添加核心钩子 */
    protected function addCoreHooks()
    {
        foreach (['bootstrapped', 'afterSetupTheme', 'init'] as $method) {
            if (\method_exists($this, $method)) {
                $this->bindAction($this, $method);
            }
        }
    }

    /** 创建不同环境的服务 */
    protected function dispatchToService()
    {
        $type = \is_admin() ? (\wp_doing_ajax() ? 'ajax' : 'admin') : 'web';

        if (isset($this->serviceTypes[$type])) {
            $this->make($this->serviceTypes[$type]);
        }

        // rest只在钩子内实例化
        if (isset($this->serviceTypes['rest'])) {
            \add_action('rest_api_init', function () {
                $this->make($this->serviceTypes['rest']);
            });
        }
    }

    /**
     * 启动引导程序
     *
     * @param array $bootstrappers
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;
        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
        \do_action('bootstrapped', $this);
    }

    /**
     * 是否已启动引导
     *
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    /** 注册基础服务 */
    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance(Container::class, $this);
        $this->instance('app', $this);
        $this->singleton('config', function () {
            return new \Impack\WP\Config\Repository($this);
        });
    }

    /** 注册核心类别名 */
    protected function registerCoreAliases()
    {
        foreach ([
            'app'        => [static::class, self::class, \Impack\Contracts\Container\Container::class],
            'config'     => [\Impack\WP\Config\Repository::class],
            'filesystem' => [\Impack\WP\Base\Filesystem::class],
        ] as $id => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($id, $alias);
            }
        }
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
     * getInstance别名
     *
     * @return static
     */
    public static function i()
    {
        return static::getInstance();
    }
}