<?php

namespace Impack\WP\Support;

class Script
{
    protected $baseUrl;

    protected $baseDir;

    protected $subDir = [];

    protected $ext;

    /**
     * 获取基于此URL的文件入队实例
     *
     * @param  string  $baseUrl
     * @param  string  $baseDir
     * @return static
     */
    public static function uri($baseUrl, $baseDir = null)
    {
        $instance = new static;

        $instance->baseUrl = rtrim($baseUrl, '\/');

        if ($baseDir) {
            $instance->baseDir = rtrim($baseDir, '\/');
        }

        return $instance;
    }

    /**
     * 设置文件类型对应的二级目录
     *
     * @param  array  $subDir
     * @return $this
     */
    public function setSubDir(array $subDir)
    {
        $this->subDir = $subDir;

        return $this;
    }

    /**
     * css文件入队
     *
     * @param  string  $name  不带后缀的文件名
     * @param  array   $deps
     * @param  string  $media
     * @param  string  $handle
     * @param  string|null  $ver
     * @return $this
     */
    public function css($name, $deps = [], $media = 'all', $handle = null, $ver = '')
    {
        $this->ext('css');

        \wp_enqueue_style(
            $this->handle($handle ?: $name),
            $this->getPath($this->baseUrl, $name),
            $deps,
            $this->ver($name, $ver),
            $media
        );

        return $this;
    }

    /**
     * js文件入队
     *
     * @param  string  $name  不带后缀的文件名
     * @param  array   $deps
     * @param  string  $footer
     * @param  string  $handle
     * @param  string|null  $ver
     * @return $this
     */
    public function js($name, $deps = [], $footer = true, $handle = null, $ver = '')
    {
        $this->ext('js');

        \wp_enqueue_script(
            $this->handle($handle ?: $name),
            $this->getPath($this->baseUrl, $name),
            $deps,
            $this->ver($name, $ver),
            $footer
        );

        return $this;
    }

    /**
     * 输出页内脚本
     *
     * @param  string  $data
     * @param  string  $handle
     * @param  string  $position
     * @return $this
     */
    public function inlineJs($data, $handle = 'jquery', $position = 'after')
    {
        \wp_add_inline_script($handle, $data, $position);

        return $this;
    }

    /**
     * 输出页内样式
     *
     * @param  string  $data
     * @param  string  $handle
     * @return $this
     */
    public function inlineCss($data, $handle = 'style')
    {
        \wp_add_inline_style($handle, $data);

        return $this;
    }

    /**
     * 设置即将入队文件的扩展名
     *
     * @param  string  $ext
     * @return $this
     */
    public function ext($ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * 替换点为横杠
     *
     * @param  string  $handle
     * @return string
     */
    protected function handle($handle)
    {
        return str_replace('.', '-', $handle);
    }

    /**
     * 返回文件版本
     *
     * @param  string  $name
     * @param  string|null  $ref
     * @return string|null
     */
    protected function ver($name, $ref = '')
    {
        if (is_null($ref)) {
            return;
        }

        if ($this->baseDir && $ver = @filectime($this->getPath($this->baseDir, $name))) {
            return $ver;
        }

        $header = \wp_get_http_headers($this->getPath($this->baseUrl, $name));

        return $header && isset($header['Last-Modified']) ? strtotime($header['Last-Modified']) : '';
    }

    /**
     * 组装文件路径，需先设置扩展名
     *
     * @param  string  $base
     * @param  string  $name
     * @return string
     */
    protected function getPath($base, $name)
    {
        if (isset($this->subDir[$this->ext])) {
            $base .= '/' . $this->subDir[$this->ext];
        }

        return $base . '/' . $name . '.' . $this->ext;
    }
}