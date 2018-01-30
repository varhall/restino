<?php

namespace Varhall\Restino\Utils;


class ArrayUtils
{
    public static function getAndRemove(array &$data, $key, $default = NULL)
    {
        $value = $default;

        if (isset($data[$key])) {
            $value = $data[$key];
            unset($data[$key]);
        }

        return $value;
    }
}