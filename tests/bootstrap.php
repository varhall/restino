<?php declare(strict_types = 1);

use Ninjify\Nunjuck\Environment;

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

Environment::setup(__DIR__);

function dump(...$args)
{
    foreach ($args as $arg) {
        var_dump($arg);
    }
}

function dumpe(...$args)
{
    dump(...$args);
    \Tester\Assert::fail('Dump variable');
    die();
}