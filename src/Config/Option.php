<?php

namespace Impack\WP\Config;

use ArgumentCountError;
use Impack\WP\Support\Arr;

/**
 * @method mixed get(string $key, $default=null)
 * @method void set(string $key, $value)
 * @method void delete(string $key)
 * @method bool has(string $key)
 */
abstract class Option
{
    protected $app;

    /** 配置名称 - 默认用作不带扩展名的字段配置文件名 */
    protected $name;

    protected $fields;

    protected $default;

    public function __construct($app = null)
    {
        if (!is_null($app)) {
            $this->app = $app;
        }
    }

    /**
     * 读取所有选项
     *
     * @return array
     */
    public function getAll()
    {
        $result = [];
        foreach ($this->getDefault() as $key => $value) {
            $result[$key] = $this->get($key, $value);
        }
        return $result;
    }

    /**
     * 移除所有选项
     */
    public function deleteAll()
    {
        foreach (array_keys($this->getDefault()) as $key) {
            $this->delete($key);
        }
    }

    /**
     * 读取全部或指定键名默认值
     *
     * @param string $key
     * @return mixed
     */
    public function getDefault($key = '')
    {
        if (!is_null($this->default)) {
            return $key ? Arr::get($this->default, $key) : $this->default;
        }

        $this->default = [];
        foreach ($this->loadFields() as $name => $field) {
            $this->default[$name] = static::getFieldValue($field);
        }

        return $this->getDefault($key);
    }

    /**
     * 获取字段的值
     *
     * @param array $field
     * @return mixed
     */
    protected static function getFieldValue($field)
    {
        if (isset($field['children'])) {
            $result = [];
            foreach ((array) $field['children'] as $key => $_field) {
                $result[$key] = static::getFieldValue($_field);
            }
            return $result;
        } else {
            return $field['value'] ?? '';
        }
    }

    /**
     * 读取所有的字段
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields ?? ($this->fields = $this->loadFields());
    }

    /**
     * 加载字段的配置文件
     *
     * @return array
     */
    protected function loadFields()
    {
        return (array) (include $this->app->configPath($this->getName() . '.php'));
    }

    /**
     * 返回配置名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 返回带前缀的完整键名
     *
     * @return string
     */
    public function getFullKey($key)
    {
        return $this->app->prefix($key);
    }

    /**
     * 返回配置实例
     *
     * @return \Impack\WP\Config\Repository
     */
    public function getConfig()
    {
        return $this->app->make('config');
    }

    public function __call($name, $params)
    {
        if (count($params) < 1) {
            throw new ArgumentCountError('Too few arguments to function ' . static::class . "::{$name}(), 0 passed");
        }

        if ($name == 'get' && !isset($params[1])) {
            $params[1] = $this->getDefault($params[0]);
        }

        $name      = $name == 'delete' ? 'forget' : $name;
        $params[0] = 'option.' . $this->getFullKey($params[0]);

        return $this->getConfig()->$name(...$params);
    }
}