<?php

namespace Impack\WP\Config;

interface LoaderContract
{
    /**
     * 加载数据
     *
     * @param  array  $keyseg
     * @param  array  $items
     */
    public function load($keyseg, &$items);

    /**
     * 更新配置项
     *
     * @param  array  $keyseg
     * @param  array  $items
     */
    public function update($keyseg, &$items);

    /**
     * 移除配置项
     *
     * @param  array  $keyseg
     * @param  array  $items
     */
    public function delete($keyseg, &$items);
}