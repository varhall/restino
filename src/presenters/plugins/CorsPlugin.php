<?php

namespace Varhall\Restino\Presenters\Plugins;


use Nette\Application\UI\Presenter;
use Nette\Http\Response;
use Varhall\Restino\Presenters\RestRequest;

class CorsPlugin extends Plugin
{
    protected  function handle(RestRequest $request, ...$args)
    {
        $request->getPresenter()->getHttpResponse()->setHeader('Access-Control-Allow-Origin', '*');

        if ($request->getPresenter()->getHttpRequest()->getMethod() === 'OPTIONS') {
            $this->sendCORSPreflightResponse($request->getPresenter());
        }

        return $request->next();
    }

    protected function sendCORSPreflightResponse($presenter)
    {
        //Preflight OPTIONS request
        $presenter->getHttpResponse()->setHeader('Access-Control-Allow-Headers', join(',', [
            'Content-Type',
            'Authorization'
        ]));

        $presenter->getHttpResponse()->setHeader('Access-Control-Allow-Methods', join(',', [
            'GET',
            'POST',
            'PUT',
            'DELETE'
        ]));

        $this->terminate(NULL, Response::S200_OK);
    }
}