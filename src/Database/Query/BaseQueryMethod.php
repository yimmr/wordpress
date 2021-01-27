<?php

namespace Impack\WP\Database\Query;

trait BaseQueryMethod
{
    /**
     * 执行 WordPress 查询函数
     *
     * @param  array  $params
     * @return array
     */
    protected function runWpGetMethod($params)
    {
        $items = call_user_func('get_' . $this->from, $params);

        if (\is_wp_error($items)) {
            return [];
        }

        if (empty($items = (array) $items) || !is_callable([current($items), 'to_array'])) {
            return $items;
        }

        return array_map(function ($object) {return $object->to_array();}, $items);
    }

    /**
     * 使用 WordPress 函数保存数据
     *
     * @param  string  $type
     * @param  int  $id
     * @param  array  $values
     * @return int
     */
    protected function runWpSaveMethod($type, $id, &$values)
    {
        if ($this->from == 'terms') {
            return $this->runWpQueryMethod($type, $id, $values['taxonomy'] ?? 'category', $values);
        }

        if ($type == 'update' && $id) {
            $values[$this->getKeyName()] = $id;
        }

        return $this->runWpQueryMethod($type, $values);
    }

    /**
     * 调用 Wordpress 函数删除数据
     *
     * @param  int  $id
     * @param  bool  $force
     * @return int
     */
    protected function runWpDeleteMethod($id, $force = false)
    {
        if ($this->from != 'terms') {
            return $this->runWpQueryMethod('delete', $id, $force);
        }

        $taxonomy = \get_term($id);
        $taxonomy = $taxonomy instanceof \WP_Term ? $taxonomy->taxonomy : '';

        return $this->runWpQueryMethod('delete', $id, $taxonomy, $force);
    }

    /**
     * 回收站功能
     *
     * @param  array|int  $id
     * @param  string  $action
     * @return bool
     */
    protected function runWpTrashMethod($id, $action = null)
    {
        if ($id && is_array($id)) {
            return $this->runQueryMany($id, function ($id) use ($action) {
                return $this->runWpQueryMethod($action, $id);
            });
        }

        return (bool) $this->runWpQueryMethod($action, $id);
    }

    /**
     * 执行 WordPress 的查询函数并统一返回值
     *
     * @param  string  $action
     * @param  mixed  $params
     * @return int
     */
    protected function runWpQueryMethod($action, ...$params)
    {
        $result = call_user_func("wp_{$action}_" . $this->getFromSingle(), ...$params);

        if (\is_wp_error($result)) {
            return 0;
        }

        if (is_numeric($result)) {
            return (int) $result;
        }

        if (is_object($result)) {
            return (int) $result->{$this->getKeyName()} ?? 0;
        }

        if (is_array($result)) {
            return (int) $result['term_id'] ?? 0;
        }

        return intval(boolval($result));
    }
}