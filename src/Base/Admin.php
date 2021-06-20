<?php

namespace Impack\WP\Base;

use Impack\WP\Base\Application;
use Impack\WP\Components\MenuPage;

class Admin
{
    /** @var \Impack\WP\Base\Application */
    protected $app;

    public function __construct(Application $app = null)
    {
        $this->app = $app;
        $this->boot();
    }

    /**
     * 启动任何应用服务或绑定钩子
     */
    public function boot()
    {}

    /**
     * 添加配置文件中的菜单页面
     */
    public function addMenu()
    {
        \add_action('admin_enqueue_scripts', [MenuPage::class, 'enqueueScripts']);
        MenuPage::addMany($this->getConfig('admin.menu_pages', []));
    }

    /**
     * 读取配置
     *
     * @param string $key
     * @param array $default
     * @return mixed
     */
    protected function getConfig($key, $default = [])
    {
        return $this->app->make('config')->get($key, $default);
    }
}