<?php

namespace Impack\WP\Database\Query;

use Impack\WP\Database\Fluent\Model;

class Meta
{
    protected $type;

    public function __construct($type = 'post')
    {
        $this->type = $type;
    }

    /**
     * 设置类型
     *
     * @param  string  $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * 新增meta数据
     *
     * @param  int  $id
     * @param  string  $key
     * @param  mixed  $value
     * @param  bool  $unique
     * @return int|false
     */
    public function addMeta($id, $key, $value, $unique = false)
    {
        return $this->getMetaFunctionName('add')($this->parseId($id), $key, $value, $unique);
    }

    /**
     * 读取meta数据
     *
     * @param  int  $id
     * @param  string  $key
     * @param  bool  $single
     * @return mixed
     */
    public function getMeta($id, $key = '', $single = true)
    {
        return $this->getMetaFunctionName('get')($this->parseId($id), $key, $single);
    }

    /**
     * 更新meta数据
     *
     * @param  int  $id
     * @param  string  $key
     * @param  mixed  $value
     * @param  mixed  $prevValue
     * @return int|bool
     */
    public function updateMeta($id, $key, $value, $prevValue = '')
    {
        return $this->getMetaFunctionName('update')($this->parseId($id), $key, $value, $prevValue);
    }

    /**
     * 删除meta数据
     *
     * @param  int  $id
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function deleteMeta($id, $key, $value = '')
    {
        return $this->getMetaFunctionName('delete')($this->parseId($id), $key, $value);
    }

    /**
     * 返回 WordPress meta交互的函数名
     *
     * @param  string  $action
     * @return string
     */
    protected function getMetaFunctionName($action)
    {
        return "{$action}_{$this->type}_meta";
    }

    /**
     * 解析出ID
     *
     * @param  int|\Impack\WP\Database\Fluent\Model  $id
     * @return mixed
     */
    protected function parseId($id)
    {
        return $id instanceof Model ? $id->getKey() : $id;
    }
}