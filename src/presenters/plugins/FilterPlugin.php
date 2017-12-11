<?php

namespace Varhall\Rest\Presenters\Plugins;

/**
 * Filters request and removes the keys which are not in $allowed array
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class FilterPlugin extends Plugin
{
    protected $allowed = [];
    
    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    protected function handle(array &$data, \Nette\Application\UI\Presenter $presenter, $method)
    {
        $data = array_intersect_key($data, array_flip($this->allowed));
    }
}
