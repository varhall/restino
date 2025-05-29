<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Schema\Endpoint;



Toolkit::test(function (): void {
    $endpoint = new Endpoint('/users/{id}', 'GET', 'UserController', 'show');
    $pattern = $endpoint->getPattern();

    Assert::equal('/users/(?P<id>[a-zA-Z0-9_-]+)', $pattern);
}, 'Endpoint converts path with variable to regex pattern');


Toolkit::test(function (): void {
    $endpoint = new Endpoint('/users/{userId}/posts/{post_id}', 'GET', 'PostController', 'show');
    $pattern = $endpoint->getPattern();

    Assert::equal('/users/(?P<userId>[a-zA-Z0-9_-]+)/posts/(?P<post_id>[a-zA-Z0-9_-]+)', $pattern);
}, 'Endpoint converts multiple parameters correctly');


Toolkit::test(function (): void {
    $endpoint = new Endpoint('/static/path', 'GET', 'StaticController', 'handle');
    $pattern = $endpoint->getPattern();

    Assert::equal('/static/path', $pattern);
}, 'Endpoint leaves static paths unchanged');