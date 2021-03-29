<?php

namespace Impack\WP\Base\Loader;

use Impack\Contracts\Config\Loader;

class Option implements Loader
{
    public function load($keyseg, &$items)
    {
        if (function_exists('get_option') && !is_null($val = \get_option($keyseg[1], null))) {
            $items[$keyseg[0]][$keyseg[1]] = $val;
        }
    }

    public function update($keyseg, &$items)
    {
        if (function_exists('update_option')) {
            return \update_option($keyseg[1], $items[$keyseg[0]][$keyseg[1]]);
        }
    }

    public function delete($keyseg, &$items)
    {
        if (function_exists('delete_option')) {
            return \delete_option($keyseg[1]);
        }
    }
}