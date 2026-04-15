<?php

use App\Jobs\ParseProfileJob;

test('horizon and queue timeouts are aligned for long running parse jobs', function () {
    $jobTimeout = (new ReflectionClass(ParseProfileJob::class))
        ->getDefaultProperties()['timeout'] ?? null;

    expect($jobTimeout)->toBeInt();

    $supervisorTimeout = config('horizon.defaults.supervisor-1.timeout');
    $supervisorTries = config('horizon.defaults.supervisor-1.tries');

    expect($supervisorTimeout)->toBeInt();
    expect($supervisorTimeout)->toBeGreaterThan($jobTimeout);
    expect($supervisorTries)->toBe(3);

    $retryAfter = config('queue.connections.redis.retry_after');

    expect($retryAfter)->toBeInt();
    expect($retryAfter)->toBeGreaterThan($supervisorTimeout);
});
