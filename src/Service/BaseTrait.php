<?php

namespace Impack\WP\Service;

trait BaseTrait
{
    protected $path;

    protected $url;

    /**
     * 初始化运行目录
     *
     * @param string|array $path
     * @param string $url
     */
    protected function registerBasePath($path = null, $url = null)
    {
        if (is_array($path)) {
            $this->options = $path;

            $path = $this->options['path'] ?? null;
            $url  = $this->options['url'] ?? null;

            unset($this->options['path'], $this->options['url']);
        }

        $this->setPath($path);
        $this->setUrl($url);
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
     * 设置运行目录
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = rtrim($path, '\/');
    }

    /**
     * 设置运行目录的URL
     *
     * @param string $url
     */
    public function setUrl(string $url)
    {
        $this->url = rtrim($url, '/');
    }

    /**
     * 返回运行目录
     *
     * @return string
     */
    public function path(string $path = '')
    {
        return $this->path . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回运行目录的URL
     *
     * @return string
     */
    public function url(string $uri = '')
    {
        return $this->url . ($uri ? '/' . $uri : $uri);
    }

    /**
     * 返回公共资源目录
     *
     * @return string
     */
    public function publicPath(string $path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'public' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
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
        return \add_action($this->methodToHook($method), [$object ?? $this, $method], $priority, $acceptedArgs);
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
        return \add_filter($this->methodToHook($method), [$object ?? $this, $method], $priority, $acceptedArgs);
    }

    /**
     * 方法名转成钩子名
     *
     * @param string $value
     * @return string
     */
    public function methodToHook($value)
    {
        return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . '_', $value), 'UTF-8');
    }
}