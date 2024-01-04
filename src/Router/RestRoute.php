<?php

namespace Varhall\Restino\Router;

use Nette\Routing\Route;

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
                'presenter' => ucfirst(strtolower($request['presenter'])),
                'mask'      => $this->baseMask,
            ];
        }

        return $request;
    }
}