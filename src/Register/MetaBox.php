<?php

namespace Impack\WP\Register;

abstract class MetaBox
{
    protected static $metaboxes = [];

    private function __construct()
    {
    }

    /**
     * 输出内容
     *
     * @param  object  $post
     */
    abstract public function render($post);

    /**
     * 保存数据
     *
     * @param  int|mixed  $postid
     */
    abstract public function save($postid);

    /**
     * 添加 Metabox
     *
     * @param  string  $id
     * @param  string  $title
     * @param  mixed  $params [$screen=null, $context='advanced', $priority='default', $callback_args=null]
     */
    public static function add($id, $title = '', ...$params)
    {
        \add_meta_box($id, $title, [static::instance(), 'render'], ...$params);
    }

    /**
     * 返回实例
     *
     * @return static
     */
    public static function instance()
    {
        return self::$metaboxes[static::class] ?? self::$metaboxes[static::class] = new static;
    }
}