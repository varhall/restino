<?php

namespace Varhall\Restino\Presenters;

use Nette\InvalidStateException;
use Varhall\Restino\Presenters\Plugins\PluginConfiguration;

/**
 * Zakladni presenter pro administracni modul
 *
 * @author Ondrej Sibrava <ondrej.sibrava@varhall.cz>
 */
trait RestPresenter
{
    use RestTrait;

    protected $plugins = [];


    public function getMethod()
    {
        $action = strtolower($this->getAction());
        if (!preg_match('/^rest/', $action))
            throw new InvalidStateException('Invalid method name');

        return preg_replace('/^rest/', '', $action);
    }


    /// NETTE ACTIONS

    public function renderRestList(array $data = [])
    {
        $result = $this->runRestMethod();

        $this->sendJson($result);
    }

    public function renderRestGet($id, array $data = [])
    {
        $result = $this->runRestMethod();

        $this->sendJson($result);
    }

    public function renderRestCreate(array $data)
    {
        $result = $this->runRestMethod();

        $this->sendJson($result);
    }

    public function renderRestUpdate($id, array $data)
    {
        $result = $this->runRestMethod();

        $this->sendJson($result);
    }

    public function renderRestDelete($id)
    {
        $result = $this->runRestMethod();

        $this->sendJson($result);
    }

    public function renderRestClone($id, array $data)
    {
        $result = $this->runRestMethod();

        $this->sendJson($result);
    }


    /// ACTIONS

    public function restList()
    {
        return $this->apiMethodSkeleton(function($class) {
            return $class::all();
        });
    }

    public function restGet($id, array $data = [])
    {
        return $this->apiMethodSkeleton(function($class) use ($id) {
            return $class::find($id);
        });
    }

    public function restCreate(array $data)
    {
        return $this->apiMethodSkeleton(function($class) use ($data) {
            return $class::create($data);
        });
    }

    public function restUpdate($id, array $data)
    {
        return $this->apiMethodSkeleton(function($class) use ($id, $data) {
            $instance = $class::find($id);
            $instance->update($data);

            return $class::find($id);
        });
    }

    public function restDelete($id)
    {
        return $this->apiMethodSkeleton(function($class) use ($id) {
            $class::find($id)->delete();

            return 'deleted';
        });
    }

    public function restClone($id, array $data)
    {
        return $this->apiMethodSkeleton(function($class) use ($id, $data) {
            $instance = $class::find($id);
            $clone = $instance->duplicate($data);

            return $clone;
        });
    }



    /// PROTECTED & PRIVATE METHODS

    private function runRestMethod()
    {
        $manager = new RestRequest($this->plugins, $this);
        return $manager->next();
    }

    private function apiMethodSkeleton(callable $body)
    {
        if ($this->modelClass()) {
            $class = $this->modelClass();

            return call_user_func($body, $class);

        } else {
            $this->sendJsonError('Method not supported', \Nette\Http\Response::S405_METHOD_NOT_ALLOWED);
        }
    }


    /// PLUGINS (middleware)

    protected function plugin($class)
    {
        $plugin = PluginConfiguration::find($class, $this->plugins);

        if (!$plugin) {
            $plugin = new PluginConfiguration($this->plugins, $class);
            $this->plugins[] = $plugin;
        }

        return $plugin;
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
        return array_map(function($x) {
            return NULL;
        }, array_flip(array_keys($this->validationDefinition())));
    }

    protected function transformDefinition()
    {
        return [];
    }
}
