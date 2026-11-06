<?php

namespace Varhall\Restino\Schema;

use Varhall\Restino\Controllers\Attributes\Path;
use Varhall\Restino\Controllers\Attributes\Action;
use Varhall\Restino\Controllers\IController;

class SchemaGenerator
{
    private array $controllers = [];


    /**
     * @param IController[] $controllers
     */
    public function __construct(array $controllers)
    {
        $this->controllers = $controllers;
    }

    public function getSchema(): Schema
    {
        $map = [];
        $groups = [];

        foreach ($this->controllers as $controller) {
            $rc = new \ReflectionClass($controller);

            $pathAttributes = $rc->getAttributes(Path::class);
            if (count($pathAttributes) === 0) {
                continue;
            }

            /** @var Path $pathAttr */
            $pathAttr = $pathAttributes[0]->newInstance();

            $group = new Group($pathAttr->path);

            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(Action::class, \ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var Action $actionAttr */
                    $actionAttr = $attr->newInstance();
                    $endpoint = new Endpoint(
                        $actionAttr->path,
                        $actionAttr->method,
                        $rc->getName(),
                        $method->getName(),
                        $actionAttr->since,
                        $actionAttr->before
                    );

                    $group->add($endpoint);

                    // check duplications - same method and path is allowed only for distinct version ranges
                    $hash = $this->getHash($endpoint);
                    foreach ($map[$hash] ?? [] as $existing) {
                        if ($existing->overlaps($endpoint)) {
                            throw new \Nette\InvalidStateException(
                                "Duplicate endpoint detected: [{$endpoint->method}] {$endpoint->path} ({$endpoint->controller}::{$endpoint->action}, {$endpoint->describeRange()})"
                                . " and [{$existing->method}] {$existing->path} ({$existing->controller}::{$existing->action}, {$existing->describeRange()})");
                        }
                    }

                    $map[$hash][] = $endpoint;

                    break;
                }
            }

            $groups[] = $group;
        }

        return new Schema($groups);
    }

    protected function getHash(Endpoint $endpoint): string
    {
        $path = preg_replace('#\{.+?\}#', '*', $endpoint->path);
        return md5("[{$endpoint->method}] {$path}");
    }
}