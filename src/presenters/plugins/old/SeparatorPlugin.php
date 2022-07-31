<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;

class SeparatorPlugin extends Plugin
{
    protected function handle(RestRequest $request, ...$args)
    {
        $separated = [];
        foreach ($args[0] as $property) {
            if (isset($request->data[$property])) {
                $separated[$property] = $request->data[$property];
                unset($request->data[$property]);
            }
        }

        $request->data = [
            'standard'  => $request->data,
            'separated' => $separated
        ];

        return $request->next();
    }
}