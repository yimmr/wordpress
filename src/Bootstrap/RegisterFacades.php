<?php

namespace Impack\WP\Bootstrap;

use Impack\Support\Facades\Facade;
use Impack\WP\Application;

class RegisterFacades
{
    protected static $aliases = [];

    protected static $registered = false;

    public function bootstrap(Application $app)
    {
        Facade::clearInstance();

        Facade::setApp($app);

        static::$aliases = array_merge(static::$aliases, (array) $app->make('config')->get('app.aliases', []));

        static::register();
    }

    /**
     * 注册自动加载门面类
     */
    protected static function register()
    {
        if (!static::$registered) {
            spl_autoload_register([static::class, 'load'], true, true);
            static::$registered = true;
        }
    }

    /**
     * 创建类的别名
     *
     * @param string $alias
     */
    protected static function load($alias)
    {
        if (isset(static::$aliases[$alias])) {
            class_alias(static::$aliases[$alias], $alias);
        }
    }
}