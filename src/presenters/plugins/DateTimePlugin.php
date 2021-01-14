<?php

namespace Varhall\Restino\Presenters\Plugins;

use Nette\DI\Container;
use Varhall\Restino\Presenters\RestRequest;

class DateTimePlugin extends Plugin
{
    const CONFIG_TIMEZONE_KEY = 'db_timezone';

    protected function handle(RestRequest $request, ...$args)
    {
        $timezone = $this->getTimezone(@$request->getPresenter()->context);

        $this->normalizeDates($request->data, $timezone);

        return $request->next();
    }

    private function getTimezone(Container $container)
    {
        return isset($container->parameters[self::CONFIG_TIMEZONE_KEY])
                    ? $container->parameters[self::CONFIG_TIMEZONE_KEY]
                    : date_default_timezone_get();
    }

    private function normalizeDates(array &$data, $timezone)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->setTimezone(new \DateTimeZone($timezone));

            } else if (is_array($value)) {
                $this->normalizeDates($value, $timezone);
            }
        }
    }
}