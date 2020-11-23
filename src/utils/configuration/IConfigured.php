<?php

namespace Varhall\Restino\Utils\Configuration;


interface IConfigured
{
    public function configuration($rules, $section);

    public function createRule($rule);
}