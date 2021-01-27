<?php

namespace Impack\WP\Database\Query;

use Impack\Support\Arr;

trait MixedQuery
{
    protected $metaQueryWith;

    protected $taxQueryWith;

    /**
     * 追加一组 meta_query 子条件
     *
     * @param  string  $key
     * @param  string|array  $value
     * @param  string  $compare
     * @param  string|null  $compare
     * @return $this
     */
    public function whereMeta($key, $value = '', $compare = '=', $type = null)
    {
        $value = compact('key', 'value', 'compare');

        if (!is_null($type)) {
            $value['type'] = $type;
        }

        $this->pushMixQueryArray('meta', $value);

        return $this;
    }

    /**
     * 设置多个 whereMeta 的关系和下次执行位置
     *
     * @param  string|false  $relation
     * @param  int|string|null|false  $index  整数或点分隔的数组索引
     * @return $this
     */
    public function whereMetaWith($relation, $index = false)
    {
        $this->setMixQueryWith('meta', $relation, $index);

        return $this;
    }

    /**
     * 追加一组 tax_query 子条件
     *
     * @param  string  $taxonomy
     * @param  int|string|array  $terms
     * @param  string  $field  [term_id|name|slug|term_taxonomy_id]
     * @param  string  $operator [IN|NOT IN|AND|EXISTS|NOT EXISTS]
     * @param  bool  $children
     * @return $this
     */
    public function whereTax($taxonomy, $terms, $field = 'term_id', $operator = 'IN', $children = true)
    {
        $value = compact('taxonomy', 'field', 'terms', 'operator');

        $value['include_children'] = $children;

        $this->pushMixQueryArray('tax', $value);

        return $this;
    }

    /**
     * 设置多个 whereTax 的关系和下次执行位置
     *
     * @param  string|false  $relation
     * @param  int|string|null|false  $index  整数或点分隔的数组索引
     * @return $this
     */
    public function whereTaxWith($relation, $index = false)
    {
        $this->setMixQueryWith('tax', $relation, $index);

        return $this;
    }

    /**
     * 给查询变量数组的子数组追加值
     *
     * @param  string  $type
     * @param  mixed  $value
     */
    protected function pushMixQueryArray($type, &$value)
    {
        $key = $this->{$type . 'QueryWith'};

        if ($key || is_int($key)) {
            $values   = Arr::get($this->where, $key = "{$type}_query.{$key}", []);
            $values[] = $value;
            Arr::set($this->where, $key, $values);
        } else {
            $this->where["{$type}_query"][] = $value;
        }
    }

    /**
     * 设置多个查询的关系和下次执行位置
     *
     * @param  string  $type
     * @param  string|false  $relation
     * @param  int|string|null|false  $index
     */
    protected function setMixQueryWith($type, $relation, $index)
    {
        if ($relation !== false) {
            $key = $this->{$type . 'QueryWith'};

            if ($key || is_int($key)) {
                Arr::set($this->where, "{$type}_query.{$key}.relation", $relation);
            } else {
                $this->where["{$type}_query"]['relation'] = $relation;
            }
        }

        if ($index !== false) {
            $this->{$type . 'QueryWith'} = $index;
        }
    }
}