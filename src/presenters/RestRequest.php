<?php

namespace Varhall\Restino\Presenters;

use Nette\Application\UI\Presenter;

class RestRequest
{
    private $plugins = [];

    private $current = 0;

    private $presenter = NULL;

    public $data = [];

    public $id = NULL;

    public $method = NULL;

    public function __construct(array $plugins, Presenter $presenter)
    {
        $this->presenter = $presenter;

        $this->plugins = array_values(array_filter($plugins, function($item) use ($presenter) { return $item->canRun($presenter->getMethod()); }));

        $this->data = $presenter->getRequest()->getParameter('data', []);
        $this->id = $presenter->getRequest()->getParameter('id');
        $this->method = $presenter->getMethod();
    }

    /**
     * @return Presenter
     */
    public function getPresenter()
    {
        return $this->presenter;
    }

    public function next()
    {
        if ($this->hasNext()) {
            $config = $this->plugins[$this->current++];
            $plugin = $config->createPlugin();

            return $plugin->run($this);

        } else {
            $args = array_filter([ $this->id, $this->data ], function($item) {
                if (is_array($item))
                    return TRUE;

                return !!$item;
            });

            return call_user_func_array([$this->presenter, 'rest' . ucfirst($this->presenter->getMethod())], $args);
        }
    }

    private function hasNext()
    {
        return $this->current < count($this->plugins);
    }
}