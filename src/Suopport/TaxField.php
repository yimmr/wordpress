<?php

namespace Impack\WP\Support;

abstract class TaxField
{
    protected $app;

    /** 新建分类法的表单字段 */
    abstract public function addField();

    /**
     * 编辑分类法的表单字段
     *
     * @param \WP_Term $term
     */
    abstract public function editField($term);

    /**
     * 保存数据
     *
     * @param int $termid
     */
    abstract public function save($termid);

    /**
     * 添加分类法表单字段
     *
     * @param  string  $taxonomy
     */
    public static function add($taxonomy)
    {
        \add_action("{$taxonomy}_add_form_fields", [new static , 'addField']);
        \add_action("{$taxonomy}_edit_form_fields", [new static , 'editField']);
        \add_action("created_{$taxonomy}", [static::class, 'saveField']);
        \add_action("edited_{$taxonomy}", [static::class, 'saveField']);
    }

    /**
     * 保存表单时执行的回调
     *
     * @param int $termid
     */
    public static function saveField($termid)
    {
        if (isset($_POST['action']) && in_array($_POST['action'], ['add-tag', 'editedtag'])) {
            (new static )->save($termid);
        }
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
        \do_action("imwp_tax_field_{$name}", $this);
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