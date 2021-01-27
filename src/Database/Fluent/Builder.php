<?php

namespace Impack\WP\Database\Fluent;

use Exception;
use Impack\Contracts\Support\Arrayable;
use Impack\Support\Traits\ForwardsCalls;
use Impack\WP\Database\Fluent\Model;
use Impack\WP\Database\Query\CoreBuilder;

class Builder
{
    use ForwardsCalls;

    protected $model;

    protected $query;

    public function __construct()
    {
        $this->query = CoreBuilder::getRawInstance();
    }

    /**
     * 返回关联的模型
     *
     * @return \Impack\WP\Database\Fluent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 设置关联的模型
     *
     * @param  \Impack\Database\Fluent\Model  $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $table   = $model->getTable();
        $support = ['users', 'posts', 'terms', 'comments'];

        if (!in_array($table, $support)) {
            throw new Exception("模型关联未支持的表：{$table}。注意：此版本仅支持" . implode('、', $support) . "表的模型。");
        }

        $this->model = $model;
        $this->query->from($table);

        return $this;
    }

    /**
     * 新增模型并返回实例
     *
     * @param  array  $attributes
     * @return \Impack\Database\Fluent\Model
     */
    public function create(array $attributes = [])
    {
        $instance = $this->model->newInstance($attributes);

        $instance->save();

        return $instance;
    }

    /**
     * 更新数据库记录
     *
     * @param  array  $values
     * @return int|bool
     */
    public function update(array $values)
    {
        return $this->query->update($values);
    }

    /**
     * 删除数据库记录
     *
     * @return bool|int
     */
    public function delete()
    {
        return $this->query->delete();
    }

    /**
     * 执行数据查询并返回结果
     *
     * @param  string|mixed  $column
     * @return \Impack\Database\Fluent\Model[]|array
     */
    public function get($column = '*')
    {
        $items = $this->query->get($column);

        if ($column == '*' || $column == 'all') {
            return array_map(function ($item) {
                return $this->model->newFromBuilder($item);
            }, $items);
        }

        return $items;
    }

    /**
     * 模型移入回收站
     *
     * @return bool|int
     */
    public function trash()
    {
        return $this->query->update(['delete_at' => time()]);
    }

    /**
     * 从回收站中恢复
     *
     * @return bool|int
     */
    public function restore()
    {
        return $this->query->update(['delete_at' => 0]);
    }

    /**
     * 通过主键查找模型
     *
     * @param  int|mixed  $id
     * @param  string|mixed  $column
     * @return \Impack\Database\Fluent\Model|mixed|null
     */
    public function find($id, $column = '*')
    {
        if ($id instanceof Arrayable) {
            $id = $id->toArray();
        }

        if (is_array($id)) {
            return $this->whereKey($id)->get($column);
        }

        return $this->whereKey($id)->first($column);
    }

    /**
     * 执行查询并返回第一个结果
     *
     * @param  string|mixed  $column
     * @return \Impack\Database\Fluent\Model|mixed|null
     */
    public function first($column = '*')
    {
        return $this->limit(1)->get($column)[0] ?? $this->model;
    }

    /**
     * 添加主键查询条件
     *
     * @param  mixed  $id
     * @return $this
     */
    public function whereKey($id)
    {
        if (is_array($id) || $id instanceof Arrayable) {
            $this->query->whereIn($this->model->getKeyName(), $id);

            return $this;
        }

        if ($id !== null && $this->model->getKeyType() === 'string') {
            $id = (string) $id;
        }

        return $this->where($this->model->getKeyName(), '=', $id);
    }

    /**
     * 调用未定义方法时，执行Query的方法
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['addMeta', 'getMeta', 'updateMeta', 'deleteMeta'])) {
            array_unshift($parameters, $this->model->getKey());

            return $this->forwardCallTo($this->query, $method, $parameters);
        }

        if ($method == 'count' || $method == 'getQueryVars') {
            return $this->query->$method();
        }

        $this->forwardCallTo($this->query, $method, $parameters);

        return $this;
    }
}