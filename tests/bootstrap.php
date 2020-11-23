<?php
require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

function test(string $description, Closure $fn): void
{
    echo $description, "\n";
    $fn();
}

function dump(...$args)
{
    foreach ($args as $arg) {
        var_dump($arg);
    }
}

function dumpe(...$args) {
    dump(...$args);
    die();
}