<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;


/**
 * Runs PHPStan with the Restino extension on the fixture and returns errors as [line => identifier]
 */
function analyse(string $file, int $minimum, int $current): array
{
    $root = realpath(__DIR__ . '/../../..');
    $temp = sys_get_temp_dir() . '/restino-phpstan-' . getmypid();
    @mkdir($temp, 0777, true);

    $config = "{$temp}/phpstan.neon";
    file_put_contents($config, implode("\n", [
        'includes:',
        "    - {$root}/phpstan/extension.neon",
        'parameters:',
        '    level: 0',
        "    tmpDir: {$temp}/cache",
        '    restinoVersioning:',
        "        minimum: {$minimum}",
        "        current: {$current}",
    ]));

    $command = sprintf(
        '%s %s analyse --no-progress --error-format=json --memory-limit=1G -c %s %s 2>&1',
        escapeshellarg(PHP_BINARY),
        escapeshellarg("{$root}/vendor/bin/phpstan"),
        escapeshellarg($config),
        escapeshellarg($file)
    );

    exec($command, $output);
    $json = json_decode(implode("\n", $output), true);

    Assert::type('array', $json, "PHPStan output:\n" . implode("\n", $output));

    $errors = [];
    foreach ($json['files'] ?? [] as $result) {
        foreach ($result['messages'] as $message) {
            $errors[$message['line']] = $message['identifier'];
        }
    }

    ksort($errors);
    return $errors;
}


Toolkit::test(function (): void {
    $errors = analyse(__DIR__ . '/../../Fixtures/PHPStan/VersionBranches.php', 2, 3);

    Assert::same([
        23 => 'restino.versioning.deadBranch',
        26 => 'restino.versioning.deadBranch',
        29 => 'restino.versioning.futureVersion',
        32 => 'restino.versioning.nonConstant',
    ], $errors);
}, 'PHPStan rule: dead, future and non-constant branches');


Toolkit::test(function (): void {
    $errors = analyse(__DIR__ . '/../../Fixtures/PHPStan/VersionBranches.php', 1, 4);

    Assert::same([
        26 => 'restino.versioning.deadBranch',
        32 => 'restino.versioning.nonConstant',
    ], $errors);
}, 'PHPStan rule: respects configured version window');
