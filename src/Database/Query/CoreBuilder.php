<?php

namespace Impack\WP\Database\Query;

use Closure;
use Impack\Contracts\Support\Arrayable;
use Impack\Support\Str;
use Impack\WP\Database\Query\BaseQueryMethod;
use Impack\WP\Database\Query\Meta;
use Impack\WP\Database\Query\MixedQuery;

class CoreBuilder
{
    use MixedQuery, SoftDelete, BaseQueryMethod;

    public $from;

    public $where = [];

    protected $metaInstance;

    protected static $instance;

    /**
     * 设置查询的表名
     *
     * @param  string  $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;

        return $this;
    }

    /**
     * 查询数据库记录
     *
     * @param  string|mixed  $column
     * @return array
     */
    public function get($column = '*')
    {
        return $this->select($column)->runWpGetMethod($this->getQueryVars());
    }

    /**
     * 新增数据库记录
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (!empty($values)) {
            $values = \apply_filters('imwp_query_insert_data', $values, $this->from);
            $values = $this->runWpSaveMethod('insert', $values['name'] ?? '', $values);
        }

        return (bool) $values;
    }

    /**
     * 更新数据库记录
     *
     * @param  array  $values
     * @return bool|int
     */
    public function update(array $values)
    {
        $ids = $this->getIdsBeforeQuery($values);

        // 是否只需移入回收站
        if (count($values) == 1 && isset($values['delete_at']) && in_array($this->from, ['posts', 'comments'])) {
            return $this->runWpTrashMethod($ids, $values['delete_at'] ? 'trash' : 'untrash');
        }

        $values = \apply_filters('imwp_query_update_data', $values, $this->from);

        if ($ids && is_array($ids)) {
            return $this->runQueryMany($ids, function ($id) use ($values) {
                return $this->runWpSaveMethod('update', $id, $values);
            });
        }

        return $this->runWpSaveMethod('update', $ids, $values);
    }

    /**
     * 删除数据库记录
     *
     * @param  mixed  $id
     * @return bool|int
     */
    public function delete($id = null)
    {
        $id = !is_null($id) ? $id : $this->getIdsBeforeQuery();

        if ($id && is_array($id)) {
            return $this->runQueryMany($id, function ($id) {
                return $this->runWpDeleteMethod($id);
            });
        }

        return (bool) $this->runWpDeleteMethod($id);
    }

    /**
     * 执行多个语句
     *
     * @param  array  $ids
     * @param  mixed  $params
     * @return int
     */
    protected function runQueryMany($ids, Closure $callback)
    {
        $count = 0;

        foreach ($ids as $id) {
            if ($callback($id)) {$count += 1;}
        }

        return $count;
    }

    /**
     * 使用主键查询数据
     *
     * @param  int|string  $id
     * @param  string|mixed  $column
     * @return mixed
     */
    public function find($id, $column = '*')
    {
        return $this->where($this->getKeyName(), $id)->first($column);
    }

    /**
     * 执行查询并返回第一个结果
     *
     * @param  string|mixed  $column
     * @return mixed|null
     */
    public function first($column = '*')
    {
        return $this->limit(1)->get($column)[0] ?? null;
    }

    /**
     * 返回记录总条数
     *
     * @return int
     */
    public function count()
    {
        if ($this->from == 'terms' || $this->from == 'comments') {
            $this->where['count'] = true;
            return (int) $this->get();
        }

        return count($this->get('id'));
    }

    /**
     * 设置要查询的列
     *
     * @param  string|mixed  $column
     * @return $this
     */
    public function select($column = '*')
    {
        if (in_array($column, ['id', 'ID', 'term_id', 'comment_ID'])) {
            $column = $this->from == 'users' ? 'ID' : 'ids';
        }

        $this->where['fields'] = $column == '*' ? 'all' : $column;

        return $this;
    }

    /**
     * 限制查询条数
     *
     * @param  int  $number
     * @param  ini|null  $offset
     * @return $this
     */
    public function limit($number, $offset = null)
    {
        $this->where[$this->from == 'posts' ? 'posts_per_page' : 'number'] = $this->getLimitValue($number);

        if (!is_null($offset)) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * 指定查询起始位置
     *
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->where['offset'] = $value;

        return $this;
    }

    /**
     * 指定页码
     *
     * @param int $value
     * @return $this
     */
    public function paged($value)
    {
        $this->where['paged'] = $value;

        return $this;
    }

    /**
     * 排序规则
     *
     * @param  string|array  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->where['orderby'] = $column;
        $this->where['order']   = $direction;

        return $this;
    }

    /**
     * 添加一个查询参数
     *
     * @param  string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        if (is_array($column)) {
            return $this->addArrayOfWheres($column);
        }

        if (func_num_args() === 2) {
            $value = $operator;
        }

        // 主键列转成查询参数
        [$column, $value] = $this->prepareKeyAndValue($column, $value);

        $this->where[$column] = $value;

        return $this;
    }

    /**
     * 设置 {$column}__in 格式的查询参数
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  bool  $in
     * @return $this
     */
    public function whereIn($column, $values, $in = true)
    {
        [$column, $values] = $this->prepareKeyAndValue($column, $values, $in ? '__in' : '__not_in');

        $this->where[$column] = $values;

        return $this;
    }

    /**
     * 设置 {$column}__not_in 格式的查询参数
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return $this
     */
    public function whereNotIn($column, $values)
    {
        $this->whereIn($column, $values, false);

        return $this;
    }

    /**
     * 获取WP_Query查询参数
     *
     * @return array
     */
    public function getQueryVars()
    {
        return $this->where;
    }

    /**
     * 恢复实例属性为默认值
     *
     * @return $this
     */
    public function flush()
    {
        $this->from  = null;
        $this->where = [];

        return $this;
    }

    /**
     * 执行查询前先从条件检出ID
     *
     * @return array|int
     */
    protected function getIdsBeforeQuery(&$values = [])
    {
        $key = $this->getKeyName();
        if (isset($values[$key])) {
            $id = $values[$key];
        } elseif (count($this->where) > 1) {
            $id = $this->get('id');
        } else {
            $id = $this->where[$this->getKeyArgName()] ?? 0;
        }

        if (is_array($id) && count($id) == 1) {
            return $id[0];
        }

        return $id;
    }

    /**
     * 返回默认的主键名称
     *
     * @return string
     */
    public function getKeyName()
    {
        switch ($this->from) {
            case 'posts':
            case 'users':
                return 'ID';
                break;
            case 'terms':
                return 'term_id';
                break;
            case 'comments':
                return 'comment_ID';
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * 返回主键对应的 WP_Query 查询参数名
     *
     * @return  bool  $in
     * @return string
     */
    protected function getKeyArgName($in = true)
    {
        switch ($this->from) {
            case 'posts':
                return $in ? 'post__in' : 'post__not_in';
                break;
            case 'users':
                return $in ? 'include' : 'exclude';
                break;
            case 'terms':
                return $in ? 'include' : 'exclude';
                break;
            case 'comments':
                return $in ? 'comment__in' : 'comment__not_in';
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * 提供正确的查询参数键和值
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  null|string  $end  添加参数后缀
     * @return array
     */
    protected function prepareKeyAndValue($key, $value, $end = null)
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if ($this->getKeyName() == $key) {
            return [$this->getKeyArgName(), (array) $value];
        }

        return [$key . $end, $value];
    }

    /**
     * 添加多个where条件
     *
     * @param  array  $column
     * @return $this
     */
    protected function addArrayOfWheres(&$column)
    {
        foreach ($column as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $this->where(...array_values($value));
            } else {
                $this->where($key, '=', $value);
            }
        }

        return $this;
    }

    /**
     * -1将根据表名得到不同值
     *
     * @param  int  $number
     * @return int|null
     */
    protected function getLimitValue($number)
    {
        if ($this->from == 'terms' && $number == -1) {
            return 0;
        } elseif ($this->from == 'comments' && $number == -1) {
            return;
        }

        return $number;
    }

    /**
     * 返回干净的查询器单例
     *
     * @return static
     */
    public static function getRawInstance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }

        return static::$instance->flush();
    }

    /**
     * 返回meta交互的单例
     *
     * @return \Impack\WP\Database\Query\Meta
     */
    public function getMetaInstance()
    {
        if (!$this->metaInstance) {
            $this->metaInstance = new Meta;
        }

        $this->metaInstance->setType($this->getFromSingle());

        return $this->metaInstance;
    }

    /**
     * 返回表名单数形式
     *
     * @return string
     */
    protected function getFromSingle()
    {
        return rtrim($this->from, 's');
    }

    /**
     * 调用不存在的方法时，将会添加查询变量
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return $this
     */
    public function __call($method, $params)
    {
        if (in_array($method, ['addMeta', 'getMeta', 'updateMeta', 'deleteMeta'])) {
            return $this->getMetaInstance()->$method(...$params);
        }

        if (count($params) === 1) {
            $params = $params[0];
        }

        return $this->where(Str::snake($method), $params);
    }
}