<?php

namespace Impack\WP\Service;

use Impack\WP\Service\Base;

class Option
{
    protected $service;

    protected $name;

    protected $fields;

    protected $default;

    protected $items = [];

    public function __construct(Base $service)
    {
        $this->service = $service;
    }

    /**
     * 保存一组数据，键名前缀可有可无
     *
     * @param array $data
     */
    public function save(array $data)
    {
        foreach (array_keys($this->getDefault()) as $key) {
            $fullKey = isset($data[$key]) ? $key : $this->fullKey($key);
            if (isset($data[$fullKey])) {
                $this->set($key, $data[$fullKey]);
            }
        }
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
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        $default = func_num_args() == 2 ? $default : $this->getDefault($key);

        return ($this->items[$key] = \get_option($this->fullKey($key), $default));
    }

    /**
     * 更新选项
     *
     * @param string $key
     * @param mixed $value
     * @return bool — 值被更新则为true，否则为false
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;

        return \update_option($this->fullKey($key), $value);
    }

    /**
     * 删除选项
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        unset($this->items[$key]);

        return \delete_option($this->fullKey($key));
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
     * 读取全部或指定的默认值
     *
     * @param string|null $key
     * @return array|mixed
     */
    public function getDefault($key = null)
    {
        if (!is_null($this->default)) {
            return $key ? ($this->default[$key] ?? null) : $this->default;
        }

        $this->default = [];
        foreach ($this->loadFields() as $name => $field) {
            $this->default[$name] = static::getFieldValue($field);
        }

        return $this->getDefault($key);
    }

    /**
     * 读取表单配置
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
        if (!file_exists($file = $this->service->path($this->getName() . '.config.php'))) {
            if (!file_exists($file = $this->service->configPath($this->getName() . '.php'))) {
                return [];
            }
        }

        return (array) include $file;
    }

    /**
     * 返回表单配置名称
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
    public function fullKey($key)
    {
        return $this->service->prefix($key);
    }

    /**
     * 检索表单字段的默认值
     *
     * @param array $field
     * @return mixed
     */
    public static function getFieldValue($field)
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
}