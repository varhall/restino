<?php

namespace Varhall\Restino\Presenters;

use Nette\Application\UI\Presenter;
use Varhall\Restino\Presenters\Results\IResult;

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


    public function __construct(Presenter $presenter)
    {
        $request = $presenter->getRequest();

        $this->data = $request->getParameter('data', []);
        $this->id = $request->getParameter('id');
        $this->method = $this->getMethod($request);

        $this->presenter = $presenter;
        //$this->plugins = array_filter($plugins, fn($item) => $item->canRun($this->method));
    }

    public function getPresenter(): Presenter
    {
        return $this->presenter;
    }

    public function run(): IResult
    {
        $action = fn(RestRequest $request): mixed => $this->runRestMethod();

        foreach ($this->plugins as $plugin) {
            $action = fn(RestRequest $request): mixed => $plugin($request, $action);
        }

        $result = $action($this);

        if (!($result instanceof IResult)) {
            $result = new Json($result);
        }

        return $result;
    }

    public function &getPlugins(): array
    {
        return $this->plugins;
    }

    public function hasPlugin(string $class): bool
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin instanceof $class) {
                return true;
            }
        }

        return false;
    }

    protected function runRestMethod(): mixed
    {
        $args = array_filter([ $this->id, $this->data ], function($item) {
            return is_array($item) || !!$item;
        });

        $method = 'rest' . ucfirst($this->method);
        return call_user_func_array([ $this->presenter, $method ], $args);
    }


    /*
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

    private function hasNext()
    {
        return $this->current < count($this->plugins);
    }
    */
}