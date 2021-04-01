<?php

namespace Impack\WP\Config;

use Impack\WP\Config\LoaderContract;

class OptionLoader implements LoaderContract
{
    public function load($keyseg, &$items)
    {
        if (isset($keyseg[1]) && !is_null($val = \get_option($keyseg[1], null))) {
            $items[$keyseg[0]][$keyseg[1]] = $val;
        }
    }

    public function update($keyseg, &$items)
    {
        if (isset($keyseg[1])) {
            return \update_option($keyseg[1], $items[$keyseg[0]][$keyseg[1]]);
        }
    }

    public function delete($keyseg, &$items)
    {
        if (isset($keyseg[1])) {
            return \delete_option($keyseg[1]);
        }
    }
}