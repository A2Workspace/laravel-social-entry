<?php

namespace A2Workspace\SocialEntry\Concerns;

use Exception;
use InvalidArgumentException;
use Illuminate\Support\Str;

trait DataAccessTrait
{
    /**
     * Try to parse to current instance.
     *
     * @param  stdClass|array  $input
     * @return self
     */
    public static function parse($input)
    {
        if (is_object($input)) {
            $input = get_object_vars($input);
        }

        if (is_array($input)) {
            $newInstnace = new self;

            $inputKeyNames = array_keys($input);
            $inputCamelKeyNames = array_map(fn ($key) => Str::camel($key), $inputKeyNames);
            $inputKeyMap = array_combine($inputCamelKeyNames, $inputKeyNames);

            $intersected = array_intersect_key($inputKeyMap, get_class_vars(__CLASS__));
            foreach ($intersected as $key => $__) {
                $newInstnace->{$key} = $input[$inputKeyMap[$key]];
            }

            return $newInstnace;
        }

        throw new InvalidArgumentException('Cannot convert from given value.');
    }

    /**
     * Convert the instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        foreach (get_class_vars(__CLASS__) as $key => $value) {
            $result[Str::snake($key)] = $this->{$key};
        }

        return $result;
    }

    /**
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return property_exists(__CLASS__, $offset);
    }

    /**
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

    /**
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->{$offset} = null;
    }

    /**
     * @param  string  $key
     * @return boolean
     */
    public function __isset($key)
    {
        return property_exists($this, Str::camel($key));
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $converted = Str::camel($key);

        if (property_exists($this, $converted)) {
            return $this->{$converted};
        }

        throw new InvalidArgumentException(
            sprintf('Undefined property: %s::$%s', __CLASS__, $key)
        );
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        $converted = Str::camel($key);

        if (property_exists($this, $converted)) {
            return $this->{$converted} = $value;
        }

        throw new InvalidArgumentException(
            sprintf('Undefined property: %s::$%s', __CLASS__, $key)
        );
    }
}
