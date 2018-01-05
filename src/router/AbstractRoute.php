<?php

namespace Varhall\Restino\Router;

use Nette\Application\Routers\Route;

/**
 * Description of RestRoute
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
abstract class AbstractRoute extends Route
{
    protected function getBase64Files(array $input, $filesKey = 'files')
    {
        if (!empty($filesKey) && !isset($input[$filesKey]))
                return [];
        
        else if (!empty($filesKey))
            $input = $input[$filesKey];
        
        // prisel surovy base64 string nebo objekt s vlastnosti base64
        if (!is_array($input) || isset($input['base64']))
            $input = [$input];

        $files = [];
        foreach ($input as $key => $file) {
            $data = $this->parseBase64( isset($file['base64']) ? $file['base64'] : $file );
            
            if (!$data)
                continue;
            
            $tmp = tmpfile();
            $tmpName = stream_get_meta_data($tmp)['uri'];
            
            fwrite($tmp, base64_decode($data['content']));
            $_FILES['base64_file_' . $key] = $tmp;

            $files[$key] = new \Nette\Http\FileUpload([
                'name'      => isset($file['name']) ? $file['name'] : 'unknown_file',
                'tmp_name'  => $tmpName,
                'type'      => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmpName),      // automatically retrieved
                'size'      => filesize($tmpName),
                'error'     => 0
            ]);
        }

        return $files;
    }
    
    protected function parseBase64($data)
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
    
    /**
     * Obali dosle parametry do $params['data'], pokud v tomto tvaru jiz neprisly
     * 
     * @param type $params
     * @param type $source
     * @param type $keys
     */
    protected function copyInputParams(&$params, $source, $keys)
    {
        $found = FALSE;
        
        foreach ($keys as $key) {
            if (isset($source[$key])) {
                $params[$key] = $source[$key];
                $found = TRUE;
            }
        }
        
        if (!$found)
            $params['data'] = $source;
    }
}
