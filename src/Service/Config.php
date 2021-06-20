<?php

namespace Impack\WP\Service;

use Impack\WP\Service\Base;

class Config
{
    protected $service;

    protected $items = [];

    protected $loaded = false;

    public function __construct(Base $service)
    {
        $this->service = $service;
    }

    /**
     * 读取选项
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->loadConfig();

        return isset($this->items[$key]) ? $this->items[$key] : $default;
    }

    /**
     * 设置选项
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->loadConfig();

        $this->items[$key] = $value;
    }

    /**
     * 移除选项
     *
     * @param string $key
     */
    public function forget($key)
    {
        unset($this->items[$key]);
    }

    /**
     * 读取所有选项
     *
     * @return array
     */
    public function getAll()
    {
        $this->loadConfig();

        return $this->items;
    }

    /**
     * 缓存选项到实例中
     */
    protected function loadConfig()
    {
        if ($this->loaded) {
            return;
        }

        $this->items  = $this->readConfigFile('service');
        $this->loaded = true;
    }

    /**
     * 读取配置文件
     *
     * @param string $filename
     * @return array
     */
    protected function readConfigFile($filename)
    {
        if (!file_exists($file = $this->service->path("{$filename}.config.php"))) {
            if (!file_exists($file = $this->service->configPath("{$filename}.php"))) {
                return [];
            }
        }

        return (array) include $file;
    }
}