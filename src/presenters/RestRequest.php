<?php

namespace Varhall\Restino\Presenters;

use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Nette\InvalidStateException;

class RestRequest
{
    /** @var array */
    protected $plugins = [];

    /** @var Presenter */
    protected $presenter;

    /** @var array */
    public $data = [];

    /** @var mixed */
    public $id;

    /** @var string */
    public $method;


    public function __construct(array $plugins, Presenter $presenter)
    {
        $request = $presenter->getRequest();

        $this->data = $request->getParameter('data', []);
        $this->id = $request->getParameter('id');
        $this->method = $this->getMethod($request);

        $this->presenter = $presenter;
        $this->plugins = array_filter($plugins, fn($item) => $item->canRun($this->method));
    }

    /**
     * @return Presenter
     */
    public function getPresenter(): Presenter
    {
        return $this->presenter;
    }

    public function next()
    {
        if ($this->hasNext()) {
            $config = $this->plugins[$this->current++];
            $plugin = $config->createPlugin();

            return call_user_func_array([ $plugin, 'run' ], array_merge([ $this ], $config->getArgs()));

        } else {
            $args = array_filter([ $this->id, $this->data ], function($item) {
                if (is_array($item))
                    return TRUE;

                return !!$item;
            });

            return call_user_func_array([$this->presenter, 'rest' . ucfirst($this->presenter->getMethod())], $args);
        }
    }


    /// PRIVATE FUNCTIONS

    protected function getMethod(Request $request): string
    {
        $action = strtolower($request->getParameter('action'));

        if (!$action || !preg_match('/^rest/', $action)) {
            throw new InvalidStateException('Invalid method name');
        }

        return preg_replace('/^rest/', '', $action);
    }

    /** @deprecated */
    private function hasNext()
    {
        return $this->current < count($this->plugins);
    }
}