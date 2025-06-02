<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Nette\Http\IResponse;
use Nette\Http\IRequest;
use Tester\Assert;
use Tests\Fixtures\Models\User;
use Varhall\Restino\Results\CollectionResult;

Toolkit::test(function (): void {
    $data = [
        new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
        new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
        new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
    ];

    $response = mock(IResponse::class);
    $response->shouldReceive('setHeader')->times(1)->with('X-Limit', CollectionResult::DEFAULT_LIMIT)->andReturnSelf();
    $response->shouldReceive('setHeader')->times(2)->with('X-Offset', CollectionResult::DEFAULT_OFFSET)->andReturnSelf();
    $response->shouldReceive('setHeader')->times(3)->with('X-Total', count($data))->andReturnSelf();
    $response->shouldReceive('setHeader')->times(4)->with('X-Next-Offset', '')->andReturnSelf();
    $response->shouldReceive('setHeader')->times(5)->with('X-Previous-Offset', '')->andReturnSelf();


    $result = new CollectionResult(\Varhall\Utilino\Collections\ArrayCollection::create($data));
    $r = $result->execute($response, mock(IRequest::class));

    Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
}, 'test ArrayCollection');
