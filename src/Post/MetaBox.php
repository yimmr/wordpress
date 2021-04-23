<?php

namespace Impack\WP\Post;

abstract class MetaBox
{
    /**
     * 渲染元框
     *
     * @param \WP_Post $post
     */
    abstract public function render($post);

    /**
     * 保存数据
     *
     * @param int $postid
     */
    abstract public function save($postid);

    /**
     * 添加元框
     *
     * @param string $id
     * @param string $title
     * @param mixed ...$params [$screen=null, $context='advanced', $priority='default', $callback_args=null]
     * @return static
     */
    public static function add($id, $title = '', ...$params)
    {
        \add_meta_box($id, $title, [new static , 'render'], ...$params);
    }
}