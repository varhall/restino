<?php

namespace Varhall\Restino\Presenters;

use Varhall\Restino\Utils\FileUtils;
use Varhall\Utilino\Collections\ICollection;


/**
 * Description of RestTrait
 *
 * @author fero
 */
trait RestTrait
{
    /**
     * Is AJAX request?
     * @return bool
     */
    public function isAjax(): bool
    {
        return true;
    }

    /**
     * Ziska z parametru ID kompozitni primarni klic, oddeleny znakem '-', vlozeny do klidu asociativniho pole
     * 
     * <b>priklad:</b><br>
     * id = 1-5<br>
     * names = ['user_id', 'role_id']<br>
     * <b>vystup:</b> [ 'user_id' => 1, 'role_id' => 5 ]<br>
     * 
     * @param array $names
     * @return type
     * @throws \Nette\InvalidArgumentException
     */
    protected function compositePrimaryKey(array $names)
    {
        $rawId = $this->getRequest()->getParameter('id');
        
        if (empty($rawId))
            throw new \Nette\InvalidArgumentException('ID parameter is empty or it does not exist');
        
        $parts = array_map('trim', explode('-', $rawId));
        
        if (count($parts) < count($names))
            throw new \Nette\InvalidArgumentException('Composite ID parameters count is less than ' . count($names));
        
        $composite = [];
        foreach ($names as $index => $key) {
            $composite[$key] = is_numeric($parts[$index]) ? intval($parts[$index]) : $parts[$index];
        }
        
        return $composite;
    }
    
    /**
     * Ziska vstupni data z pozadavku
     * 
     * @return array
     */
    protected function getRequestData()
    {
        return $this->getParameter('data', []);
    }

    /**
     * Ziska vstupni soubory z pozadavku
     *
     * @param $key Nazev klice pozadavku, kde se nachazi soubor(y)
     * @return array
     */
    protected function getRequestFiles($key = 'file')
    {
        $data = $this->getParameter('data', []);
        return FileUtils::retrieveFiles($data, $key);
    }

    /**
     * Expands object with nested subobject properties
     *
     * @param $object Object to be expanded
     * @param $path Properties in format "property.nested.subnested"
     * @return ICollection
     */
    public function expand($object, $path)
    {
        if (empty($path))
            return $object;

        if ($object instanceof ICollection) {
            return $object->map(function ($item) use ($path) {
                return $this->expand($item, $path);
            });
        }

        $path = explode('.', $path);
        $property = array_shift($path);
        $path = implode('.', $path);

        $result = $object->toArray();
        $result[$property] = $this->expand($object->$property, $path);

        return $result;
    }
}
