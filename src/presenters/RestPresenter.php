<?php

namespace Varhall\Restino\Presenters;

/**
 * Zakladni presenter pro administracni modul
 *
 * @author Ondrej Sibrava <ondrej.sibrava@varhall.cz>
 */
trait RestPresenter
{
    use RestTrait;

    /// NETTE ACTIONS

    public function renderRestList()
    {
        $this->checkSupportedMethod('list');
        $this->callPlugins('list');
        $result = $this->restList();

        $this->sendJson($result);
    }

    public function renderRestGet($id)
    {
        $this->checkSupportedMethod('get');
        $this->callPlugins('get');
        $result = $this->restGet($id);

        $this->sendJson($result);
    }

    public function renderRestCreate(array $data)
    {
        $this->checkSupportedMethod('create');
        $this->callPlugins('create', $data);
        $result = $this->restCreate($data);

        $this->sendJson($result);
    }

    public function renderRestUpdate($id, array $data)
    {
        $this->checkSupportedMethod('update');
        $this->callPlugins('update', $data);
        $result = $this->restUpdate($id, $data);

        $this->sendJson($result);
    }

    public function renderRestDelete($id)
    {
        $this->checkSupportedMethod('delete');
        $this->callPlugins('delete');
        $result = $this->restDelete($id);

        $this->sendJson($result);
    }

    public function renderRestClone($id, array $data)
    {
        $this->checkSupportedMethod('clone');
        $this->callPlugins('clone');
        $result = $this->restClone($id, $data);

        $this->sendJson($result);
    }

    /// ACTIONS

    public function restList()
    {
        $this->apiMethodSkeleton(function($class) {
            return $class::all();
        });
    }

    public function restGet($id)
    {
        $this->apiMethodSkeleton(function($class) use ($id) {
            return $class::find($id);
        });
    }

    public function restCreate(array $data)
    {
        $this->apiMethodSkeleton(function($class) use ($data) {
            return $class::create($data);
        });
    }

    public function restUpdate($id, array $data)
    {
        $this->apiMethodSkeleton(function($class) use ($id, $data) {
            $instance = $class::find($id);
            $instance->update($data);

            return $instance;
        });
    }

    public function restDelete($id)
    {
        $this->apiMethodSkeleton(function($class) use ($id) {
            $class::find($id)->delete();

            return 'deleted';
        });
    }

    public function restClone($id, array $data)
    {
        $this->apiMethodSkeleton(function($class) use ($id, $data) {
            $instance = $class::find($id);
            $clone = $instance->duplicate($data);

            return $clone;
        });
    }

    /// PROTECTED & PRIVATE METHODS

    private function apiMethodSkeleton(callable $body)
    {
        if ($this->modelClass()) {
            $class = $this->modelClass();

            $response = call_user_func($body, $class);

            $this->sendJson($response);

        } else {
            $this->methodNotSupported();
        }
    }

    public function callPlugins($method, array &$data = [], $plugins = NULL)
    {
        if (!$plugins)
            $plugins = $this->plugins();

        foreach ($plugins as $plugin) {
            if (!is_array($plugin))
                $plugin = [ 'plugin' => $plugin ];

            if (isset($plugin['except']) && in_array($method, $plugin['except']))
                continue;

            if (!isset($plugin['only']) || (isset($plugin['only']) && in_array($method, $plugin['only'])))
                $plugin['plugin']->run($data, $this, $method);
        }
    }

    private function checkSupportedMethod($method)
    {
        if (is_array($this->methodsOnly()) && !empty($this->methodsOnly()) && !in_array($method, $this->methodsOnly()))
            $this->methodNotSupported();
    }

    private function methodNotSupported()
    {
        $this->sendJsonError('Method not supported', \Nette\Http\Response::S405_METHOD_NOT_ALLOWED);
    }

    /// CONFIG METHODS

    /**
     * Class name which API presenter works with
     *
     * @return class
     */
    protected function modelClass()
    {
        return NULL;
    }

    protected function methodsOnly()
    {
        return [];
    }

    protected function plugins()
    {
        return [
            [ 'plugin' => new Plugins\FilterPlugin($this->filterDefinition()), 'only' => [ 'create', 'update' ] ],
            new Plugins\TransformPlugin($this->transformDefinition(), $this->validationDefinition()),
            [ 'plugin' => new Plugins\ValidatePlugin($this->validationDefinition()), 'only' => [ 'create', 'update' ] ]
        ];
    }

    /**
     * Model validation rules. It is array of allowed properties. Properties not
     * written there are automatically filtered out.
     *
     * Definition is divided into two default sections - create/update.
     * If the section is defined, validation rules are used only in this action.
     * If not, rules are used in both create/update actions
     *
     * Example:
     *
     * return [
     *     'name'          => 'string',
     *     'description'   => ['string'],
     *     'start_date'    => [
     *         'create' => ['required', 'string'],
     *         'update' => ['required', 'string']
     *     ],
     *     'end_date'      => 'date',
     *     'timetable'     => 'array',
     *     'options'       => 'array'
     * ];
     *
     * @return array
     */
    protected function validationDefinition()
    {
        return [];
    }

    /**
     * Model filtration rules. By default this method returns keys of validationDefinition method
     *
     * @return array
     */
    protected function filterDefinition()
    {
        return array_keys($this->validationDefinition());
    }

    protected function transformDefinition()
    {
        return [];
    }
}
