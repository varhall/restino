<?php

namespace Varhall\Restino\Presenters\Results;

use Varhall\Restino\Presenters\RestRequest;

class UrlRedirection implements IResult
{
    public $url = null;

    public function __construct($url)
    {
        $this->url = $url;
    }
    
    public function run(RestRequest $request)
    {
        $request->getPresenter()->redirectUrl($this->url);
    }
}
