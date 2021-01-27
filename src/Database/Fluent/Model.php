<?php

namespace Impack\WP\Database\Fluent;

use ArrayAccess;
use Exception;
use Impack\Contracts\Support\Arrayable;
use Impack\Contracts\Support\Jsonable;
use Impack\Support\Func;
use Impack\Support\Str;
use Impack\Support\Traits\ForwardsCalls;
use Impack\WP\Database\Eloquent\JsonEncodingException;
use Impack\WP\Database\Fluent\Attributes;
use JsonSerializable;

/**
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder where($column, $operator = null, $value = null)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder whereIn($column, $values, $in = true)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder whereNotIn($column, $values)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder select($column = '*')
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder orderBy($column, $direction = 'asc')
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder limit($number, $offset = null)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder offset($value)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder paged($value)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder onlyTrashed()
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder getQueryVars()
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder whereMeta($key, $value = '', $compare = '=', $type = null)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder whereMetaWith($relation, $index = false)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder whereTax($taxonomy, $terms, $field = 'term_id', $operator = 'IN', $children = true)
 * @method static \Impack\WP\Database\Fluent\Builder|\Impack\WP\Database\Query\CoreBuilder whereTaxWith($relation, $index = false)
 * @method static array get($column = '*')
 * @method static int count()
 * @method static static create(array $attributes = [])
 * @method static static find($id, $column = '*')
 * @method static static first($column = '*')
 * @method int|false addMeta($key, $value, $unique = false)
 * @method mixed getMeta($key = '', $single = true)
 * @method int|bool updateMeta($key, $value, $prevValue = '')
 * @method bool deleteMeta($key, $value = '')
 */
abstract class Model implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    use Attributes, ForwardsCalls;

    protected $table;

    protected $primaryKey = 'ID';

    protected $keyType = 'int';

    public $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->reSetDefaultKeyName();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * 重置默认主键名称
     */
    protected function reSetDefaultKeyName()
    {
        switch ($this->getTable()) {
            case 'terms':
                $this->primaryKey = 'term_id';
                break;
            case 'comments':
                $this->primaryKey = 'comment_ID';
                break;
            default:break;
        }
    }

    /**
     * 从数据库中获取所有数据或模型
     *
     * @param  string|mixed  $column
     * @return array|\Impack\Database\Fluent\Model[]
     */
    public static function all($column = '*')
    {
        return (new static )->newQuery()->limit(-1)->get($column);
    }

    /**
     * 销毁给定ID的模型
     *
     * @param  array|mixed  $ids
     * @return bool|int
     */
    public static function destroy($ids)
    {
        $ids = is_array($ids) ? $ids : func_get_args();

        if (count($ids) === 0) {
            return 0;
        }

        $instance = new static;

        return $instance->whereIn($instance->getKeyName(), $ids)->delete();
    }

    /**
     * 删除模型对应的数据库记录
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        if (!$this->exists) {
            return false;
        }

        $this->setKeysForSaveQuery($this->newQuery())->delete();

        $this->exists = false;

        return true;
    }

    /**
     * 更新模型数据并保存到数据库
     *
     * @param  array  $attributes
     * @return int
     */
    public function update(array $attributes = [])
    {
        if (!$this->exists) {
            return false;
        }

        return $this->fill($attributes)->save();
    }

    /**
     * 模型移入回收站
     *
     * @return bool|int
     */
    public function trash()
    {
        if (!$this->exists) {
            return false;
        }

        return $this->setKeysForSaveQuery($this->newQuery())->trash();
    }

    /**
     * 从回收站中恢复
     *
     * @return bool|int
     */
    public function restore()
    {
        if (!$this->exists) {
            //return false;
        }

        $result = $this->setKeysForSaveQuery($this->newQuery())->restore();

        if ($result) {
            $this->exists = true;
        }

        return $result;
    }

    /**
     * 更新或插入数据至数据库，取决于是否存在记录
     *
     * @return bool
     */
    public function save()
    {
        $query = $this->newQuery();

        if ($this->exists) {
            $saved = $this->isDirty() ? $this->performUpdate($query) : true;
        } else {
            $saved = $this->performInsert($query);
        }

        if ($saved) {
            $this->syncOriginal();
        }

        return $saved;
    }

    /**
     * 用指定数组填充模型属性
     *
     * @param  array  $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * 添加同步到数据库的meta数据
     *
     * @param  array  $attributes
     * @return $this
     */
    public function fillMeta(array $meta)
    {
        $this->setAttribute('meta', $meta);

        return $this;
    }

    /**
     * 返回模型关联的数据表
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? Str::snake(Func::classBaseName($this)) . 's';
    }

    /**
     * 设置模型关联的数据表
     *
     * @param  string  $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * 返回模型主键
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * 设置模型主键
     *
     * @param  string  $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * 返回主键的值
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * 返回主键的数据类型
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * 设置主键的数据类型
     *
     * @param  string  $type
     * @return $this
     */
    public function setKeyType($type)
    {
        $this->keyType = $type;

        return $this;
    }

    /**
     * 创建指定模型的新实例
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array) $attributes);

        $model->exists = $exists;

        $model->setTable($this->getTable());

        return $model;
    }

    /**
     * 创建存在原始属性的模型实例
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder(array $attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);

        $model->setAttributes($attributes, true);

        return $model;
    }

    /**
     * 返回与模型关联的查询构建器
     *
     * @return \Impack\WP\Database\Fluent\Builder
     */
    public function newQuery()
    {
        return $this->newFluentBuilder()->setModel($this);
    }

    /**
     * 创建新的模型构建器
     *
     * @return \Impack\WP\Database\Fluent\Builder
     */
    public function newFluentBuilder()
    {
        return new Builder;
    }

    /**
     * 执行模型插入操作
     *
     * @param  \Impack\Database\Fluent\Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        $attributes = $this->getAttributes();

        if (!empty($attributes)) {
            $query->insert($attributes);
            $this->exists = true;
        }

        return true;
    }

    /**
     * 执行模型更新操作
     *
     * @param  \Impack\Database\Fluent\Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $this->setKeysForSaveQuery($query)->update($dirty);
            $this->syncChanges();
        }

        return true;
    }

    /**
     * 用主键和值添加到查询条件
     *
     * @param  \Impack\Database\Fluent\Builder  $query
     * @return \Impack\Database\Fluent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    /**
     * 获取执行语句的主键值
     *
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        return $this->original[$this->getKeyName()] ?? $this->getKey();
    }

    /**
     * 模型转为数组
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * 模型转JSON字符串
     *
     * @param  int  $options
     * @return string
     *
     * @throws \Impack\WP\Database\Eloquent\JsonEncodingException
     */
    public function toJson($options = 0)
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw JsonEncodingException::forModel($this, json_last_error_msg());
        }

        return $json;
    }

    /**
     * 对象转换为可序列化的JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * 执行未定义方法时转发构建器的方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }

    /**
     * 静态执行未定义方法时新建实例执行
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static )->$method(...$parameters);
    }

    /**
     * 读取模型属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * 设置模型属性
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * isset方式判断模型属性是否存在
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * unset方式移除模型属性
     *
     * @param  string  $key
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * 数组形式-判断模型属性是否存在
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return !is_null($this->getAttribute($offset));
    }

    /**
     * 数组形式-读取模型属性
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * 数组形式-设置模型属性
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * 数组形式-删除模型属性
     *
     * @param  mixed  $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * 模型转为字符串
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}