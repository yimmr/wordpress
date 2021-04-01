<?php

namespace Impack\WP\Base;

use Impack\Container\Container;

abstract class Application extends Container
{
    use ApplicationTrait;

    protected $prefix = '';

    protected $serviceTypes = [];

    protected $hasBeenBootstrapped = false;

    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $this->setPath($path);
        }
        $this->registerBaseBindings();
        $this->registerCoreAliases();
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