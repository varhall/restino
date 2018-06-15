<?php

namespace Varhall\Restino\Presenters\Plugins;

use Varhall\Restino\Presenters\RestRequest;

/**
 * Filters request and removes the keys which are not in $allowed array
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class FilterPlugin extends Plugin
{
    protected  function handle(RestRequest $request, ...$args)
    {
        $rules = $this->presenterCall($request->getPresenter(), 'filterDefinition');

        $rules = array_filter($rules, function($rule) use ($request) {
            $rule = is_string($rule) ? array_map('trim', explode(',', $rule)) : (array) $rule;

            return empty($rule) || in_array($request->method, $rule);
        });

        $request->data = array_intersect_key($request->data, array_flip(array_keys($rules)));

        return $request->next();
    }
}
