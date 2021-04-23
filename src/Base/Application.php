<?php

namespace Impack\WP\Base;

use Impack\Container\Container;
use Impack\WP\Service\BaseTrait;

abstract class Application extends Container
{
    use BaseTrait;

    protected $prefix = 'imwp';

    protected $serviceTypes = [];

    protected $hasBeenBootstrapped = false;

    public function __construct($path = null, $url = null)
    {
        $this->registerBasePath($path, $url);
        $this->registerBaseBindings();
        $this->registerCoreAliases();
    }

    /** 服务提供者 */
    abstract public function provider();

    /**
     * 返回配置文件目录
     *
     * @return string
     */
    public function configPath(string $path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'config' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回文件存储目录
     *
     * @return string
     */
    public function storagePath(string $path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'storage' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
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
}