<?php

namespace Impack\WP\Service;

trait PathTrait
{
    protected $path;

    protected $URL;

    /**
     * 设置执行目录
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = rtrim($path, '\/');

        return $this;
    }

    /**
     * 设置执行目录URL
     *
     * @param string $URL
     * @return $this
     */
    public function setURL(string $URL)
    {
        $this->URL = rtrim($URL, '/');

        return $this;
    }

    /**
     * 返回执行目录
     *
     * @return string
     */
    public function path(string $path = '')
    {
        return $this->path . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回执行目录URL
     *
     * @return string
     */
    public function URL(string $URI = '')
    {
        return $this->URL . ($URI ? '/' . $URI : $URI);
    }

    /**
     * 返回公共资源目录URL
     *
     * @return string
     */
    public function publicURL(string $URI = '')
    {
        return $this->URL . '/assets' . ($URI ? '/' . $URI : $URI);
    }

    /**
     * 返回公共资源目录
     *
     * @return string
     */
    public function publicPath(string $path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'assets' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回视图路径
     *
     * @param string $path
     * @return string
     */
    public function viewPath($path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'templates' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * 返回配置文件路径
     *
     * @param string $path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->path . \DIRECTORY_SEPARATOR . 'config' . ($path ? \DIRECTORY_SEPARATOR . $path : $path);
    }
}