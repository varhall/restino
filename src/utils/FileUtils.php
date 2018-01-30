<?php

namespace Varhall\Restino\Utils;

class FileUtils
{
    public static function fromBase64($base64, $filename = NULL)
    {
        $data = self::parseBase64($base64);

        if (!$data)
            throw new \Nette\InvalidArgumentException('Given string is not valid base64 file');

        if (empty($filename))
            $filename = 'unknown_file';

        $tmp = tmpfile();
        $tmpName = stream_get_meta_data($tmp)['uri'];

        fwrite($tmp, base64_decode($data['content']));
        $_FILES['base64_file_' . \Nette\Utils\Strings::webalize($filename)] = $tmp;

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
        if (!is_string($data) || !preg_match('/^data:.+;.+,.+$/i', $data))
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