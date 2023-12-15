<?php

function runPluginTest($plugin, $data, $validation, $transform) {
    $temp = [];
    $plugin = new \Varhall\restino\src\presenters\plugins\PluginConfiguration($temp, $plugin);
    $request = new RestRequestMock([ $plugin ], new PresenterMock($validation, $transform), $data);

    return $request->next();
}

class RestRequestMock extends \Varhall\restino\src\presenters\RestRequest
{
    public function __construct(array $plugins, \Nette\Application\UI\Presenter $presenter, $data)
    {
        $this->presenter = $presenter;
        $this->plugins = $plugins;

        $this->data = $data;
        $this->id = null;
        $this->method = 'list';
    }
}

class PresenterMock extends \Nette\Application\UI\Presenter
{

    protected $validation = [];
    protected $transform  = [];

    public function __construct($validation, $transform)
    {
        $this->validation = $validation;
        $this->transform = $transform;
    }

    public function __call(string $name, array $args)
    {
        if (preg_match('/^rest/i', $name))
            return $args[0];
    }

    protected function validationDefinition()
    {
        return $this->validation;
    }

    protected function transformDefinition()
    {
        return $this->transform;
    }
}