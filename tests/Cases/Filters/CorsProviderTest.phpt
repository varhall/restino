<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tests\Fixtures\Versioning\Kit;
use Varhall\Restino\Filters\Cors;
use Varhall\Restino\Filters\CorsHeaderProvider;
use Varhall\Restino\Results\SimpleResult;
use Varhall\Restino\Results\Termination;
use Varhall\Restino\Versioning\HeaderVersionResolver;
use Varhall\Restino\Versioning\VersioningCorsHeaders;


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();

    $provider = new VersioningCorsHeaders(Kit::settings(), new HeaderVersionResolver('X-Api-Version'));
    $context = Kit::context(Kit::endpoint('detail'), $response, [], 'OPTIONS', [ 'versioning.cors' => $provider ]);

    $result = (new Cors())->execute($context, fn() => new SimpleResult(1));

    Assert::type(Termination::class, $result);
    Assert::same('Content-type, Authorization, X-Api-Version', $headers['Access-Control-Allow-Headers']);
    Assert::same('X-Api-Min-Version, X-Api-Current-Version', $headers['Access-Control-Expose-Headers']);
}, 'Cors: preflight contains headers from providers');


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();

    $provider = new class implements CorsHeaderProvider {
        public function allowHeaders(): array { return ['authorization', 'X-Custom']; }
        public function exposeHeaders(): array { return ['X-Total']; }
    };

    $context = Kit::context(Kit::endpoint('detail'), $response, [], 'GET', [ 'custom' => $provider ]);

    $cors = new Cors([
        'allow_headers'     => ['Authorization'],
        'expose_headers'    => ['X-Total', 'X-Limit'],
    ]);

    $result = $cors->execute($context, fn() => new SimpleResult(1));

    Assert::type(SimpleResult::class, $result);
    Assert::same('Authorization, X-Custom', $headers['Access-Control-Allow-Headers']);
    Assert::same('X-Total, X-Limit', $headers['Access-Control-Expose-Headers']);
}, 'Cors: provider headers are merged case-insensitively with options');


Toolkit::test(function (): void {
    [$response, $headers] = Kit::response();

    $context = Kit::context(Kit::endpoint('detail'), $response);
    (new Cors())->execute($context, fn() => new SimpleResult(1));

    Assert::false(isset($headers['Access-Control-Expose-Headers']));
    Assert::same('Content-type, Authorization', $headers['Access-Control-Allow-Headers']);
}, 'Cors: without providers and expose_headers the output is unchanged');
