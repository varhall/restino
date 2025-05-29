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
                        $method->getName()
                    );

                    $group->add($endpoint);

                    break;
                }
            }

            $groups[] = $group;
        }

        return new Schema($groups);
    }
}