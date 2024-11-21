<?php

namespace App\Models;
use ArrayAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Model implements ArrayAccess, JsonSerializable
{
    /**
     * @var array The original attributes.
     */
    protected $original = [];

    /**
     * Create a new Eloquent model instance.
     *
     * @return void
     */
    public function __construct(public array $attributes = [])
    {
        $this->original = $attributes;
    }

    /**
     * Create a new Collection instance.
     *
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function getAttribute($key)
    {
        if (!$key) {
            return '';
        }

        return $this->attributes[$key] = $this->attributes[$key] ?? null;

    }

    public function setAttribute($key, $value)
    {
        return $this->attributes[$key] = $value;
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     */
    public function offsetExists($offset): bool
    {
        try {
            return !is_null($this->getAttribute($offset));
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement', 'incrementQuietly', 'decrementQuietly'])) {
            return $this->$method(...$parameters);
        }

        if ($resolver = $this->relationResolver(static::class, $method)) {
            return $resolver($this);
        }

        if (
            Str::startsWith($method, 'through') &&
            method_exists($this, $relationMethod = Str::of($method)->after('through')->lcfirst()->toString())
        ) {
            return $this->through($relationMethod);
        }

        return $this->forwardCallTo($this->newQuery(), $method, $parameters);
    }


}
