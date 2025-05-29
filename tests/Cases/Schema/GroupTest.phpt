<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Schema\Group;
use Varhall\Restino\Schema\Endpoint;


Toolkit::test(function (): void {
    $group = new Group('/api');

    $endpoint = new Endpoint('/users', 'GET', 'UserController', 'index');
    $group->add($endpoint);

    Assert::equal('/api/users', $endpoint->path);
    Assert::count(1, $group->endpoints);
    Assert::same($endpoint, $group->endpoints[0]);
}, 'Group adds endpoint with normalized path');


Toolkit::test(function (): void {
    $group = new Group('/api/');
    $endpoint = new Endpoint('users/{id}', 'GET', 'UserController', 'show');

    $group->add($endpoint);

    Assert::equal('/api/users/{id}', $endpoint->path);
}, 'Group adds endpoint and trims slashes');

