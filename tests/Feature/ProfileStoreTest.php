<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profile can be stored without skills or experiences', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profiles.store'), [
            'name' => 'Jane Doe',
            'headline' => null,
            'about' => null,
            'location' => null,
            'raw_text' => str_repeat('a', 60),
        ]);

    $response->assertRedirect(route('profiles.create'));

    $this->assertDatabaseHas('profiles', [
        'name' => 'Jane Doe',
    ]);
});
