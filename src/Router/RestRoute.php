<?php

namespace Varhall\Restino\Router;

use Nette\Application\Routers\Route;

class RestRoute extends Route
{
    protected string $baseMask;

    public function __construct(string $mask)
    {
        parent::__construct($mask . '[/<path .+>]', []);

        $this->baseMask = $mask;
    }

    public function match(\Nette\Http\IRequest $httpRequest): ?array
    {
        $request = parent::match($httpRequest);

        if ($request !== null) {
            return [
                'presenter' => $request['presenter'],
                'mask'      => $this->baseMask,
            ];
        }

        return $request;
    }
}