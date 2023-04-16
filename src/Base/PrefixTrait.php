<?php

namespace Impack\WP\Base;

trait PrefixTrait
{
    /**
     * 设置前缀
     *
     * @param string $prefix
     */
    public function setPrefix($prefix = '')
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * 为指定名称附加前缀
     *
     * @param  string   $name
     * @param  string   $delimiter
     * @return string
     */
    public function prefix($name, $delimiter = '_')
    {
        return $this->prefix . $delimiter . $name;
    }

    /**
     * 创建私有保护键名
     *
     * @param  string   $key
     * @return string
     */
    public function privKey($key)
    {
        return '_' . $this->prefix . '_' . $key;
    }
}