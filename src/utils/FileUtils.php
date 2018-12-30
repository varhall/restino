<?php

namespace Varhall\Restino\Utils;

class FileUtils
{
    public static function retrieveFiles(array &$request, $property, $removePropety = TRUE)
    {
        $result = [];

        if (!isset($request[$property]))
            return $result;

        $files = $request[$property];

        if (!is_array($files) || (isset($files['data']) || isset($files['base64'])) )
            $files = [ $files ];

        foreach ($files as $file) {
            if (is_string($file))
                $file = ['data' => $file];

            // compatibility - rename "base64" property to "data"
            if (is_array($file) && isset($file['base64'])) {
                $file['data'] = $file['base64'];
                unset($file['base64']);
            }

            if (!isset($file['name']))
                $file['name'] = NULL;

            $result[] = \Varhall\Utilino\Files\FileUtils::fromBase64($file['data'], $file['name']);
        }

        if ($removePropety)
            unset($files[$property]);

        return $result;
    }
}