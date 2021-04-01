<?php

namespace Impack\WP\Base;

use Impack\WP\Base\ApplicationTrait;

abstract class ApplicationBase
{
    use ApplicationTrait;

    protected static $instance;

    protected $prefix = 'imwp';

    protected $serviceTypes = [];

    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $this->setPath($path);
        }
        static::setInstance($this);
    }

    /**
     * 启动应用
     */
    public function boot()
    {
        $this->addCoreHooks();
        $this->provider();
        $this->dispatchToService();
    }

    /** 创建不同环境的服务 */
    protected function dispatchToService()
    {
        $type = \is_admin() ? (\wp_doing_ajax() ? 'ajax' : 'admin') : 'web';

        if (isset($this->serviceTypes[$type])) {
            new $this->serviceTypes[$type]($this);
        }

        // rest只在钩子内实例化
        if (isset($this->serviceTypes['rest'])) {
            \add_action('rest_api_init', function () {
                new $this->serviceTypes['rest']($this);
            });
        }
    }

    /**
     * 返回应用实例
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * 设置应用实例
     *
     * @param \Impack\WP\Base\ApplicationBase $app
     */
    public static function setInstance($app)
    {
        return static::$instance = $app;
    }
}