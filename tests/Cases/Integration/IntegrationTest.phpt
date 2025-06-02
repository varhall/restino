<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Presenters\UserInput;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Varhall\Restino\Controllers\Action;
use Varhall\Restino\Controllers\Attributes\Get;
use Varhall\Restino\Controllers\Attributes\Post;
use Varhall\Utilino\Collections\ICollection;

/// Test classes

class TestController implements \Varhall\Restino\Controllers\IController
{
    #[Get('/')]
    public function index(): ICollection
    {
        return User::all();
    }

    #[Get('/{id}')]
    public function get(int $id): User
    {
        //dumpe($this->context->getByType(\Varhall\Restino\Middlewares\MiddlewareManager::class));
        return User::find($id);
    }

    #[Get('/foo')]
    public function foo(): mixed
    {
        return 'foo';
    }

    #[Post('/')]
    public function create(\DateTime $created, UserInput $data): User
    {
        dumpe($data);
        return new User();
        //dump($created);
        //dumpe($data);
    }

    // /api/users/more
    #[Get('/more/<id>/<foo>')]
    public function more(int $id, int $foo): object
    {
        return (object) [
            'p'   => $foo,
            'id'    => $id,
            'foo'   => 'bar',
            'bar'   => 'foo'
        ];
    }
}


/// Test cases

Toolkit::test(function (): void {
    Assert::true(true);
});


//Toolkit::test(function (): void {
//
//    $data = [
//        'foo' => 'foo value',
//        'bar' => 'bar value',
//        'baz' => 'baz value'
//    ];
//
//    $controller = new TestController();
//    $method = new \ReflectionMethod($controller, 'test');
//    $action = new Action($method, $data);
//
//    $result = $action($controller);
//
//    Assert::type(Result::class, $result);
//    Assert::equal('result data', $result->getData());
//
//    Assert::equal('test', $action->getName());
//    Assert::same($method, $action->getFunction());
//}, 'testExecute');

