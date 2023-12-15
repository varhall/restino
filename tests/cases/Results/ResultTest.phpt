<?php

namespace Tests\Cases\Results;

use Nette\Http\IResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Fixtures\Models\User;
use Varhall\Restino\Results\Result;
use Varhall\Utilino\ISerializable;

require_once __DIR__ . '/../../bootstrap.php';

class ResultTest extends BaseTestCase
{
    public function testExecute_scalar()
    {
        $data = 10;

        $result = new Result($data);
        $r = $result->execute(mock(IResponse::class));

        Assert::equal($data, $r);
    }

    public function testExecute_model()
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];
        $object = mock(ISerializable::class);
        $object->shouldReceive('toArray')->andReturn($data);

        $result = new Result($object);
        $r = $result->execute(mock(IResponse::class));

        Assert::equal($data, $r);
    }

    public function testExecute_traversable()
    {
        $data = [
            new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
            new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
            new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
        ];

        $result = new Result(new \ArrayObject($data));
        $r = $result->execute(mock(IResponse::class));

        Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
    }

    public function testExecute_array()
    {
        $data = [
            new User([ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ]),
            new User([ 'name' => 'karel', 'surname' => 'kral', 'age' => 40 ]),
            new User([ 'name' => 'viktor', 'surname' => 'lusk', 'age' => 18 ]),
        ];

        $result = new Result($data);
        $r = $result->execute(mock(IResponse::class));

        Assert::equal(array_map(fn($x) => $x->toArray(), $data), $r);
    }

    public function testExecute_object()
    {
        $data = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];

        $result = new Result((object) $data);
        $r = $result->execute(mock(IResponse::class));

        Assert::equal($data, $r);
    }

    public function testExecute_mapper()
    {
        $source = [ 'name' => 'pepa', 'surname' => 'novak', 'age' => 50 ];
        $map = [ 'property' => 'changed' ];

        $result = new Result((object) $source);
        $result->addMapper(function($result, $item) use ($source, $map) {
            Assert::equal($source, $result);
            Assert::equal((object) $source, $item);

            return $map;
        });

        $r = $result->execute(mock(IResponse::class));

        Assert::equal($map, $r);
    }
}

(new ResultTest())->run();
