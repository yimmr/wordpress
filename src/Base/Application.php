<?php

namespace Impack\WP\Base;

use Impack\Container\Container;
use Impack\Support\Str;

abstract class Application extends Container
{
    protected $path;

    protected $url;

    protected $prefix = '';

    /** 绑定不同类型的服务 */
    protected $serviceTypes = [];

    protected $hasBeenBootstrapped = false;

    /** 服务提供者 */
    abstract public function provider();

    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $this->setPath($path);
        }
        $this->registerBaseBindings();
        $this->registerCoreAliases();
    }

    /** 设置应用目录 */
    public function setPath(string $path)
    {
        $this->path = rtrim($path, '\/');
    }

    /** 设置应用目录Url */
    public function setUrl(string $url)
    {
        $this->url = rtrim($url, '/');
    }

    /**
     * 返回应用目录
     *
     * @return string
     */
    public function path(string $path = '')
    {
        return $this->path . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回配置文件目录
     *
     * @return string
     */
    public function configPath(string $path = '')
    {
        return $this->path . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回公共资源目录
     *
     * @return string
     */
    public function publicPath(string $path = '')
    {
        return $this->path . DIRECTORY_SEPARATOR . 'public' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回应用目录Url
     *
     * @return string
     */
    public function url(string $uri = '')
    {
        return $this->url . ($uri ? '/' . $uri : $uri);
    }

    /**
     * 返回公共资源Url
     *
     * @return string
     */
    public function publicUrl(string $uri = '')
    {
        return $this->url . '/public' . ($uri ? '/' . $uri : $uri);
    }

    /**
     * 给指定键名添加前缀或返回前缀名
     *
     * @param string $key
     * @param bool $sanke 是否带下划线
     * @return string
     */
    public function prefix($key = '', $sanke = true)
    {
        return $this->prefix . ($sanke ? '_' : '') . $key;
    }

    /**
     * 开始加载WP时的时间(s)
     *
     * @return float
     */
    public function timestart()
    {
        global $timestart;
        return $timestart;
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
        foreach (['bootstrapped', 'pluginsLoaded', 'setupTheme', 'afterSetupTheme', 'init'] as $method) {
            if (\method_exists($this, $method)) {
                \add_action(Str::snake($method), [$this, $method]);
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
        $this->singleton('config', \Impack\WP\Base\Config::class);
    }

    /** 注册核心类别名 */
    protected function registerCoreAliases()
    {
        foreach ([
            'app'        => [static::class, self::class, \Impack\Contracts\Container\Container::class],
            'config'     => [\Impack\WP\Base\Config::class, \Impack\Contracts\Config\Repository::class],
            'filesystem' => [\Impack\WP\Base\Filesystem::class],
        ] as $id => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($id, $alias);
            }
        }
    }
}