<?php

namespace Varhall\Restino\Versioning\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Varhall\Restino\Versioning\ClientVersion;

/**
 * Checks branches on client version - ClientVersion::before(n):
 *  - n must be a constant integer (literal or class constant)
 *  - n <= minimum is a dead branch (no client is older)
 *  - n > current refers to a future version
 *
 * @implements Rule<MethodCall>
 */
class ClientVersionBeforeRule implements Rule
{
    public function __construct(
        private int $minimum,
        private int $current,
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!($node->name instanceof Identifier) || $node->name->toLowerString() !== 'before') {
            return [];
        }

        $callee = $scope->getType($node->var);
        if (!(new ObjectType(ClientVersion::class))->isSuperTypeOf($callee)->yes()) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) === 0) {
            return [];
        }

        $values = $scope->getType($args[0]->value)->getConstantScalarValues();

        if (count($values) !== 1 || !is_int($values[0])) {
            return [
                RuleErrorBuilder::message('Argument of ClientVersion::before() must be a constant integer (literal or class constant), so the branch can be checked.')
                    ->identifier('restino.versioning.nonConstant')
                    ->build(),
            ];
        }

        $version = $values[0];

        if ($version <= $this->minimum) {
            return [
                RuleErrorBuilder::message("ClientVersion::before({$version}) is always false, minimum supported version is {$this->minimum}. Remove the dead branch.")
                    ->identifier('restino.versioning.deadBranch')
                    ->build(),
            ];
        }

        if ($version > $this->current) {
            return [
                RuleErrorBuilder::message("ClientVersion::before({$version}) refers to a future version, current version is {$this->current}.")
                    ->identifier('restino.versioning.futureVersion')
                    ->build(),
            ];
        }

        return [];
    }
}
