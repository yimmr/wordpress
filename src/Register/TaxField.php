<?php

namespace Impack\WP\Register;

abstract class TaxField
{
    protected static $instances = [];

    private function __construct()
    {
    }

    /** 新建页的表单字段 */
    abstract public function addField();

    /**
     * 编辑页的表单字段
     *
     * @param  object  $term
     */
    abstract public function editField($term);

    /**
     * 保存数据
     *
     * @param  int  $termid
     */
    abstract public function save($termid);

    /**
     * 添加分类法表单字段
     *
     * @param  string  $taxonomy
     */
    public static function add($taxonomy)
    {
        \add_action("{$taxonomy}_add_form_fields", [static::instance(), 'addField']);
        \add_action("{$taxonomy}_edit_form_fields", [static::instance(), 'editField']);
        \add_action("created_{$taxonomy}", [static::class, 'saveField']);
        \add_action("edited_{$taxonomy}", [static::class, 'saveField']);
    }

    /**
     * 保存表单时触发
     *
     * @param  int  $termid
     */
    public static function saveField($termid)
    {
        if (isset($_POST['action']) && in_array($_POST['action'], ['add-tag', 'editedtag'])) {
            static::instance()->save($termid);
        }
    }

    /**
     * 返回实例
     *
     * @return static
     */
    public static function instance()
    {
        return self::$instances[static::class] ?? self::$instances[static::class] = new static;
    }
}