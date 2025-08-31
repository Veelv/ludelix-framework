<?php

namespace Ludelix\Session;

class Store
{
    /**
     * The session name.
     *
     * @var string
     */
    protected $name;

    /**
     * The session attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new session store.
     *
     * @param  string  $name
     * @param  array   $attributes
     * @return void
     */
    public function __construct($name, array &$attributes = [])
    {
        $this->name = $name;
        $this->attributes = &$attributes;
    }

    /**
     * Get an attribute from the session.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return data_get($this->attributes, $key, $default);
    }

    /**
     * Put a key / value pair in the session.
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return void
     */
    public function put($key, $value = null)
    {
        if (! is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            data_set($this->attributes, $arrayKey, $arrayValue);
        }
    }

    /**
     * Determine if an item exists in the session.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all of the session attributes.
     *
     * @return array
     */
    public function all()
    {
        return $this->attributes;
    }

    /**
     * Flash a key / value pair to the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function flash($key, $value)
    {
        $this->put($key, $value);
        $this->push('_flash.new', $key);
        $this->removeFromOldFlashData([$key]);
    }

    /**
     * Age the flash data for the session.
     *
     * @return void
     */
    public function ageFlashData()
    {
        $this->forget($this->get('_flash.old', []));
        $this->put('_flash.old', $this->get('_flash.new', []));
        $this->put('_flash.new', []);
    }

    /**
     * Remove the given keys from the old flash data.
     *
     * @param  array  $keys
     * @return void
     */
    protected function removeFromOldFlashData(array $keys)
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Get the old input from the session.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getOldInput($key = null, $default = null)
    {
        return $this->get("_old_input.{$key}", $default);
    }

    /**
      * Determine if the session has old input for a given key.
      *
      * @param  string|null  $key
      * @return bool
      */
    public function hasOldInput($key = null)
    {
        return $this->has("_old_input".($key ? '.'.$key : ''));
    }

    /**
     * Set the old input for the session.
     *
     * @param  array  $input
     * @return void
     */
    public function setOldInput(array $input)
    {
        $this->put('_old_input', $input);
    }

    /**
     * Remove an item from the session.
     *
     * @param  string|array  $keys
     * @return void
     */
    public function forget($keys)
    {
        data_forget($this->attributes, $keys);
    }

    /**
     * Remove an item from the session.
     *
     * @param  string|array  $keys
     * @return void
     */
    public function remove($keys)
    {
        $this->forget($keys);
    }

    /**
     * Push a value onto a session array.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->put($key, $array);
    }

    /**
     * Save the session data to storage.
     *
     * @return void
     */
    public function save()
    {
        $this->ageFlashData();
        // The session data is already in the $_SESSION superglobal
        // because we passed it by reference.
        session_write_close();
    }
}
