<?php

namespace Impack\WP\Service;

trait ConfigTrait
{
    protected $config;

    /**
     * 读取配置项
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function config($key, $default = null)
    {
        return static::getInstance()->getConfig($key, $default);
    }

    /**
     * 读取配置项
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        if (!is_array($this->config)) {
            $this->config = $this->readConfigFile('service');
        }

        return $this->config[$key] ?? $default;
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

    /**
     * 返回配置文件路径
     *
     * @param string $filename
     * @return string
     */
    protected function getConfigFile($filename)
    {
        $file = $this->configPath("{$filename}.php");

        return file_exists($file) ? $file : $this->configPath("{$filename}.config.php");
    }

    /**
     * 读取配置文件
     *
     * @param string $filename
     * @return array
     */
    protected function readConfigFile($filename)
    {
        if (file_exists($file = $this->getConfigFile($filename))) {
            return (array) include $file;
        }

        return [];
    }
}