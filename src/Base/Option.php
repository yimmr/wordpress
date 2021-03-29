<?php

namespace Impack\WP\Base;

use ArgumentCountError;
use Impack\Support\Arr;
use Impack\WP\Base\Application;

/**
 * @method mixed get(string $key, $default=null)
 * @method void set(string $key, $value)
 * @method void delete(string $key)
 * @method bool has(string $key)
 */
abstract class Option
{
    protected $app;

    protected $fields;

    protected $default;

    /** 配置文件名 */
    protected $name = 'theme';

    public function __construct(Application $app)
    {
        $this->app = $app;
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
        foreach ($this->loadFieldsFile() as $name => $field) {
            $this->default[$name] = static::getFieldValue($field);
        }

        return $this->getDefault($key);
    }

    /**
     * 读取所有字段
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields ?? ($this->fields = $this->loadFieldsFile());
    }

    /**
     * 返回配置文件名
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 加载字段的配置文件
     *
     * @return array
     */
    protected function loadFieldsFile()
    {
        return (array) (include $this->app->configPath($this->getName() . '.php'));
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

    public function __call($name, $params)
    {
        if (count($params) < 1) {
            throw new ArgumentCountError('Too few arguments to function ' . static::class . "::{$name}(), 0 passed");
        }

        if ($name == 'get' && !isset($params[1])) {
            $params[1] = $this->getDefault($params[0]);
        }

        $name      = $name == 'delete' ? 'forget' : $name;
        $params[0] = 'option.' . $this->app->prefix($params[0]);

        return $this->app->make('config')->$name(...$params);
    }
}