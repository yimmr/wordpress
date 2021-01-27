<?php

namespace Impack\WP\Database\Query;

trait SoftDelete
{
    /**
     * 设置为只读回收站数据
     *
     * @return $this
     */
    public function onlyTrashed()
    {
        if ($this->from == 'posts') {
            $this->where['post_status'] = 'trash';
        } elseif ($this->from == 'comments') {
            $this->where['status'] = 'trash';
        }

        return $this;
    }
}