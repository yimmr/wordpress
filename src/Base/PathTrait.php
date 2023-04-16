<?php

namespace Impack\WP\Base;

trait PathTrait
{
    protected $basePath = '';

    protected $baseURL = '';

    /**
     * 设置根目录
     *
     * @param string $basePath
     */
    public function setPath($basePath = '')
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

    /**
     * 设置根URL
     *
     * @param string $baseURL
     */
    public function setURL($baseURL = '')
    {
        $this->baseURL = rtrim($baseURL, '/');

        return $this;
    }

    /**
     * 设置跟目录和URL
     *
     * @param string $basePath
     * @param string $baseURL
     */
    public function setBase($basePath = '', $baseURL = '')
    {
        $this->setPath($basePath);
        $this->setURL($baseURL);

        return $this;
    }

    /**
     * 返回基于根目录的路径
     *
     * @param  string   $path
     * @return string
     */
    public function path($path = '')
    {
        return $this->basePath . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回配置文件路径
     *
     * @param  string   $path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->basePath . \DIRECTORY_SEPARATOR . 'config' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回公共资源目录
     *
     * @param  string   $path
     * @return string
     */
    public function publicPath($path = '')
    {
        return $this->basePath . \DIRECTORY_SEPARATOR . 'assets' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回基于根URL的URL
     *
     * @param  string   $uri
     * @return string
     */
    public function url($uri = '')
    {
        return $this->baseURL . ($uri ? '/' . $uri : $uri);
    }

    /**
     * 返回公共资源目录URL
     *
     * @param  string   $uri
     * @return string
     */
    public function publicURL($uri = '')
    {
        return $this->baseURL . '/assets' . ($uri ? '/' . $uri : $uri);
    }
}