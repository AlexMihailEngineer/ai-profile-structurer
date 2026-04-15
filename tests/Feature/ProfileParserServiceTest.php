<?php

use App\Services\ProfileParserService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.nvidia.key', 'test-key');
    config()->set('services.nvidia.url', 'https://example.test/v1');
    config()->set('services.nvidia.model', 'test-model');
});

test('parse throws when the API response is missing message content', function () {
    Http::fake([
        'example.test/*' => Http::response(['choices' => []], 200),
    ]);

    $service = app(ProfileParserService::class);

    $service->parse('some raw text');
})->throws(Exception::class, 'Missing response content');

test('parse throws when the model returns invalid JSON', function () {
    Http::fake([
        'example.test/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '{invalid json',
                ],
            ]],
        ], 200),
    ]);

    $service = app(ProfileParserService::class);

    $service->parse('some raw text');
})->throws(Exception::class, 'Invalid JSON returned by model.');

test('parse returns decoded array when the model returns valid JSON', function () {
    Http::fake([
        'example.test/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'name' => 'Jane Doe',
                        'headline' => null,
                        'about' => null,
                        'location' => null,
                        'skills' => [],
                        'experiences' => [],
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $service = app(ProfileParserService::class);

    $result = $service->parse('some raw text');

    expect($result)->toBeArray();
    expect($result['name'])->toBe('Jane Doe');
});
