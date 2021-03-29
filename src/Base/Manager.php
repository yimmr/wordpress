<?php

namespace Impack\WP\Base;

use Impack\WP\Base\Application;
use Impack\WP\Support\Register;

class Manager
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 注册类型及相关分类法、元框等功能
     */
    public function registerPostType()
    {
        $posttypes  = $this->getConfig('posttype');
        $taxonomies = $this->getConfig('taxonomy');
        Register::postType($posttypes, $taxonomies);
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