<?php

namespace Varhall\Restino\Test\Utils\Validation;

use Tester\Assert;
use Tester\Expect;
use Tester\TestCase;
use Varhall\Restino\Utils\Validation\Rules\Required;
use Varhall\Restino\Utils\Validation\Rules\System;
use Varhall\Restino\Utils\Validation\Validator;

require __DIR__ . '/../../bootstrap.php';

class ValidatorCodeTest extends TestCase
{
    // createRule

    public function testCreateRule_Rule()
    {
        $result = (new Validator())->createRule(new System('string'));

        Assert::type(System::class, $result);
        Assert::equal('string', $result->name);
        Assert::null( $result->arguments);
        Assert::equal([], $result->modifiers);
    }

    public function testCreateRule_String()
    {
        $result = (new Validator())->createRule('string');

        Assert::type(System::class, $result);
        Assert::equal('string', $result->name);
        Assert::null( $result->arguments);
        Assert::equal([], $result->modifiers);
    }

    public function testCreateRule_Required()
    {
        $result = (new Validator())->createRule('required:only=create');

        Assert::type(Required::class, $result);
        Assert::equal('required', $result->name);
        Assert::null( $result->arguments);
        Assert::equal([ 'only' => 'create' ], $result->modifiers);
    }
}

(new ValidatorCodeTest())->run();