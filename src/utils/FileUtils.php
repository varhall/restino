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

            $result[] = self::fromBase64($file['data'], $file['name']);
        }

        if ($removePropety)
            unset($files[$property]);

        return $result;
    }

    public static function fromBase64($base64, $filename = NULL)
    {
        $data = self::parseBase64($base64);

        if (!$data)
            throw new \Nette\InvalidArgumentException('Given string is not valid base64 file');

        return self::fromBinary(base64_decode($data['content']), $filename);
    }

    public static function fromBinary($data, $filename = NULL)
    {
        if (empty($filename))
            $filename = 'unknown_file';

        $tmp = tmpfile();
        $tmpName = stream_get_meta_data($tmp)['uri'];

        fwrite($tmp, $data);
        $_FILES['file_' . \Nette\Utils\Strings::webalize($filename)] = $tmp;

        return new \Nette\Http\FileUpload([
            'name'      => $filename,
            'tmp_name'  => $tmpName,
            'type'      => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmpName),      // automatically retrieved
            'size'      => filesize($tmpName),
            'error'     => 0
        ]);
    }

    protected static function parseBase64($data)
    {
        // validates Data URI scheme (https://en.wikipedia.org/wiki/Data_URI_scheme)
        if (!is_string($data) || !preg_match('/^data:.+;.+,.+$/i', substr($data, 0, 200)))
            return NULL;

        list($head, $content) = explode(',', $data, 2);

        $head = str_replace('data:', '', $head);
        list($type) = explode(';', $head);

        return [
            'type'      => $type,
            'content'   => $content
        ];
    }
}