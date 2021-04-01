<?php

namespace Impack\WP\Base;

trait ApplicationTrait
{
    protected $path;

    protected $url;

    /** 服务提供者 */
    abstract public function provider();

    /**
     * 设置应用目录
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = rtrim($path, '\/');
    }

    /**
     * 设置应用目录的URL
     *
     * @param string $url
     */
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
     * 返回应用目录的URL
     *
     * @return string
     */
    public function url(string $uri = '')
    {
        return $this->url . ($uri ? '/' . $uri : $uri);
    }

    /**
     * 返回公共资源目录的URL
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

    /** 添加核心钩子 */
    protected function addCoreHooks()
    {
        foreach (['bootstrapped', 'pluginsLoaded', 'setupTheme', 'afterSetupTheme', 'init'] as $method) {
            if (\method_exists($this, $method)) {
                $this->bindAction($this, $method);
            }
        }
    }

    /**
     * 指定对象方法与动作钩子绑定
     *
     * @param Object $object
     * @param string $method
     * @param int $priority
     * @param int $acceptedArgs
     * @return true
     */
    public function bindAction($object, $method, $priority = 10, $acceptedArgs = 1)
    {
        return \add_action(static::methodToHook($method), [$object ?? $this, $method], $priority, $acceptedArgs);
    }

    /**
     * 指定对象方法与过滤钩子绑定
     *
     * @param Object $object
     * @param string $method
     * @param int $priority
     * @param int $acceptedArgs
     * @return true
     */
    public function bindFilter($object, $method, $priority = 10, $acceptedArgs = 1)
    {
        return \add_filter(static::methodToHook($method), [$object ?? $this, $method], $priority, $acceptedArgs);
    }

    /**
     * 将方法名转成钩子名
     *
     * @param string $value
     * @return string
     */
    public static function methodToHook($value)
    {
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . '_', $value);
        return mb_strtolower($value, 'UTF-8');
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
}