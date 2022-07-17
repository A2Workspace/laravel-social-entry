<?php

namespace A2Workspace\SocialEntry\Contracts;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;

interface DataAccess extends ArrayAccess, Arrayable
{
    /**
     * Try to parse to current instance.
     *
     * @param  stdClass|array  $input
     * @return self
     */
    public static function parse($input);
}
