<?php

namespace Impack\WP\Service;

trait OptionTrait
{
    protected $options;

    /**
     * 读取配置项
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function option($key, $default = null)
    {
        return static::getInstance()->getOption($key, $default);
    }

    /**
     * 读取配置项
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (!is_array($this->options)) {
            $this->options = $this->readConfigFile('service');
        }

        return $this->options[$key] ?? $default;
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