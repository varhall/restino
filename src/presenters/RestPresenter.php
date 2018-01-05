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
        $this->callPlugins('list');
        $this->restList();
    }

    public function renderRestGet($id)
    { 
        $this->callPlugins('get');
        $this->restGet($id);
    }

    public function renderRestCreate(array $data)
    {
        $this->callPlugins('create', $data);
        $this->restCreate($data);
    }

    public function renderRestUpdate($id, array $data)
    {
        $this->callPlugins('update', $data);
        $this->restUpdate($id, $data);
    }

    public function renderRestDelete($id)
    {
        $this->callPlugins('delete');
        $this->restDelete($id);
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
        $this->apiMethodSkeleton(function($class) use ($data, $action) {
            
            $data = $this->filterRequestData($data, $this->filterDefinition());
            $validationRules = $this->addValidationRule($this->extractValidationDefinition('create'), 'required');
            $this->validateRequestData($data, $validationRules);
            
            return $action ? call_user_func($action, $data) : $class::create($data);
        });
    }
    
    public function restUpdate($id, array $data)
    {
        $this->apiMethodSkeleton(function($class) use ($id, $data, $action) {
            
            $data = $this->filterRequestData($data, $this->filterDefinition());
            $this->validateRequestData($data, $this->extractValidationDefinition('update'));
            
            $instance = $class::find($id);
            
            if ($action)
                call_user_func($action, $id, $data);
            else
                $instance->update($data);
            
            return $instance;
        });
    }
    
    public function restDelete($id)
    {
        $this->apiMethodSkeleton(function($class) use ($id) {
            $class::find($id)->delete();
            
            return 'ok';
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
    
    private function callPlugins($method, array &$data = [])
    {
        foreach ($this->plugins() as $plugin) {
            if (!is_array($plugin))
                $plugin = [ 'plugin' => $plugin ];
                
            if (isset($plugin['except']) && in_array($method, $plugin['except']))
                continue;
            
            if (!isset($plugin['only']) || (isset($plugin['only']) && in_array($method, $plugin['only'])))
                $plugin['plugin']->run($data, $this, $method);
        }
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
    
    protected function plugins()
    {
        return [
            [ 'plugin' => new Plugins\FilterPlugin($this->filterDefinition()), 'only' => [ 'create', 'update' ] ],
            new Plugins\TransformPlugin($this->transformDefinition()),
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
