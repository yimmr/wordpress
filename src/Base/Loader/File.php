<?php

namespace Impack\WP\Base\Loader;

use Impack\Contracts\Config\Loader;

class File implements Loader
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 加载数据
     *
     * @param  array  $keyseg
     * @param  array  $items
     */
    public function load($keyseg, &$items)
    {
        if (file_exists($file = $this->path($keyseg[0]))) {
            $items[$keyseg[0]] = require $file;
        }
    }

    /**
     * 更新保存数据
     *
     * @param  array  $keyseg
     * @param  array  $items
     * @return bool
     */
    public function update($keyseg, &$items)
    {
        $filename = $keyseg[0];

        $data = $items[$filename];
        $data = is_array($data) ? var_export($data, true) : $data;
        $data = "<?php\r\nreturn {$data};";

        return (bool) file_put_contents($this->path($filename), $data);
    }

    /**
     * 删除数据
     *
     * @param  array  $keyseg
     * @param  array  $items
     * @return bool
     */
    public function delete($keyseg, &$items)
    {
        $filename = $keyseg[0];

        // 不存在文件名的配置时，理解为删除源文件
        if (!isset($items[$filename])) {
            return @unlink($this->path($filename));
        }

        return $this->update($keyseg, $items);
    }

    /**
     * 返回配置文件路径
     *
     * @param sttring $filename
     * @return string
     */
    protected function path($filename)
    {
        return $this->app->configPath($filename . '.php');
    }
}