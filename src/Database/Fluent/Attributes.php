<?php

namespace Impack\WP\Database\Fluent;

use Impack\Support\Arr;

trait Attributes
{
    protected $attributes = [];

    protected $original = [];

    protected $changes = [];

    /**
     * 设置模型属性
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($key) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取指定模型属性
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (!$key) {
            return;
        }

        if (method_exists(self::class, $key)) {
            return;
        }

        return $this->attributes[$key] ?? null;
    }

    /**
     * 返回所有模型属性
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * 重置模型属性数组
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * 当前属性同步至原始属性
     *
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->getAttributes();

        return $this;
    }

    /**
     * 获取保存前修改过的属性
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->getAttributes() as $key => $value) {
            if (!$this->originalIsEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * 保存前是否改过属性
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        return $this->hasChanges($this->getDirty(), is_array($attributes) ? $attributes : func_get_args());
    }

    /**
     * 保存前是否未改过属性
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isClean($attributes = null)
    {
        return !$this->isDirty(...func_get_args());
    }

    /**
     * 重新记录改过的属性
     *
     * @return $this
     */
    public function syncChanges()
    {
        $this->changes = $this->getDirty();

        return $this;
    }

    /**
     * 是否修改过模型属性
     *
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function wasChanged($attributes = null)
    {
        return $this->hasChanges($this->getChanges(), is_array($attributes) ? $attributes : func_get_args());
    }

    /**
     * 返回修改过的属性
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * 确定属性是否有过更改
     *
     * @param  array  $changes
     * @param  array|string|null  $attributes
     * @return bool
     */
    protected function hasChanges($changes, $attributes = null)
    {
        if (empty($attributes)) {
            return count($changes) > 0;
        }

        foreach (Arr::wrap($attributes) as $attribute) {
            if (isset($changes[$attribute])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断新属性是否与原属性相同
     *
     * @param  string  $key
     * @return bool
     */
    public function originalIsEquivalent($key)
    {
        if (!isset($this->original[$key])) {
            return false;
        }

        $attribute = Arr::get($this->attributes, $key);
        $original  = Arr::get($this->original, $key);

        if ($attribute === $original) {
            return true;
        } elseif (is_null($attribute)) {
            return false;
        }

        return is_numeric($attribute) && is_numeric($original) && strcmp((string) $attribute, (string) $original) === 0;
    }
}