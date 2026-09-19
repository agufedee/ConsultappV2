<?php

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/consultapp');

    $response->assertRedirect('/consultapp/login');
});

it('renders the login page', function () {
    $response = $this->get('/consultapp/login');

    $response->assertStatus(200);
    $response->assertSeeLivewire(Login::class);
});

it('allows authenticated users to access the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'admin@consultapp.test',
    ]);

    $this->actingAs($user);

    $response = $this->get('/consultapp');

    $response->assertStatus(200);
});
