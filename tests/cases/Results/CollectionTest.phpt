<?php

namespace Tests\Cases\Results;

use Nette\Database\Table\Selection;
use Nette\Http\IResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Restino\Results\CollectionResult;
use Varhall\Restino\Results\Result;
use Varhall\Utilino\Collections\ArrayCollection;

require_once __DIR__ . '/../../bootstrap.php';

class CollectionTest extends BaseTestCase
{
    public function testPaginate_structure()
    {
        $data = ArrayCollection::range(1, 10);

        $result = new CollectionResult($data);
        $result->paginate(3, 2);
        $test = $result->execute(mock(IResponse::class));

        $expect = [
            'pagination' => [
                'limit'         => 3,
                'offset'        => [
                    'current'       => 2,
                    'next'          => 5,
                    'previous'      => null
                ],
                'total'         => 10,
            ],
            'results'   => [ 3, 4, 5 ]
        ];

        Assert::equal($expect, $test);
    }

    public function testPagination_Next()
    {
        $data = ArrayCollection::range(1, 10);
        $result = new CollectionResult($data);

        // 1st page
        $result->paginate(3, 2);
        $test = $result->execute(mock(IResponse::class));
        Assert::equal(2, $test['pagination']['offset']['current']);
        Assert::equal([ 3, 4, 5 ],  $test['results']);

        // 2nd page
        $result = new CollectionResult($data);
        $result->paginate($test['pagination']['limit'], $test['pagination']['offset']['next']);
        $test = $result->execute(mock(IResponse::class));
        Assert::equal(5, $test['pagination']['offset']['current']);
        Assert::equal([ 6, 7, 8 ],  $test['results']);

        // 3rd page
        $result = new CollectionResult($data);
        $result->paginate($test['pagination']['limit'], $test['pagination']['offset']['next']);
        $test = $result->execute(mock(IResponse::class));
        Assert::equal(8, $test['pagination']['offset']['current']);
        Assert::equal([ 9, 10 ],  $test['results']);
        Assert::null($test['pagination']['offset']['next']);
    }

    public function testPagination_Previous()
    {
        $data = ArrayCollection::range(1, 10);
        $result = new CollectionResult($data);

        // 1st page
        $result->paginate(3, 8);
        $test = $result->execute(mock(IResponse::class));
        Assert::equal(8, $test['pagination']['offset']['current']);
        Assert::equal([ 9, 10 ],  $test['results']);

        // 2nd page
        $result = new CollectionResult($data);
        $result->paginate($test['pagination']['limit'], $test['pagination']['offset']['previous']);
        $test = $result->execute(mock(IResponse::class));
        Assert::equal(5, $test['pagination']['offset']['current']);
        Assert::equal([ 6, 7, 8 ],  $test['results']);

        // 3rd page
        $result = new CollectionResult($data);
        $result->paginate($test['pagination']['limit'], $test['pagination']['offset']['previous']);
        $test = $result->execute(mock(IResponse::class));
        Assert::equal(2, $test['pagination']['offset']['current']);
        Assert::equal([ 3, 4, 5 ],  $test['results']);
        Assert::null($test['pagination']['offset']['previous']);
    }

    public function testOrder()
    {
        $data = mock(Selection::class);
        $data->shouldReceive('order')->once()->with('foo ASC')->andReturnSelf();
        $data->shouldReceive('order')->once()->with('bar DESC')->andReturnSelf();
        $data->shouldReceive('limit')->with(CollectionResult::DEFAULT_LIMIT, CollectionResult::DEFAULT_OFFSET)->andReturnSelf();
        $data->shouldReceive('count')->andReturn(10);

        $result = new CollectionResult($data);
        $result->addOrder('foo', false);
        $result->addOrder('bar', true);

        $test = $result->execute(mock(IResponse::class));

        Assert::type('array', $test);
    }

    public function testFromResult()
    {
        $mapper = function($a, $b) {};
        $data = ArrayCollection::range(1, 10);

        $core = new Result($data);
        $core->addMapper($mapper);

        $result = CollectionResult::fromResult($core);

        Assert::same($data, $result->getData());
        Assert::equal([ $mapper ], $result->mappers);
    }
}

(new CollectionTest())->run();
