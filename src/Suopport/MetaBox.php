<?php

namespace Impack\WP\Support;

abstract class MetaBox
{
    protected $app;

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

    /**
     * 执行钩子
     *
     * @param string $name
     */
    public function doAction($name = '')
    {
        if (!$name) {
            $name = explode('\\', static::class);
            $name = end($name);
        }
        \do_action("imwp_meta_box_{$name}", $this);
    }

    /**
     * 设置应用实例
     *
     * @param Object $app
     */
    public function setApp($app)
    {
        $this->app = $app;
    }
}