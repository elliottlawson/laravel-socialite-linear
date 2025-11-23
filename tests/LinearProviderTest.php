<?php

use ElliottLawson\SocialiteLinear\LinearProvider;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User;
use Mockery as m;

it('can instantiate the provider', function () {
    $provider = new LinearProvider(
        Request::create('http://localhost'),
        'client-id',
        'client-secret',
        'http://localhost/callback'
    );

    expect($provider)->toBeInstanceOf(LinearProvider::class);
});

it('generates correct authorization url', function () {
    $request = Request::create('http://localhost');
    $request->setLaravelSession($session = m::mock('Illuminate\Contracts\Session\Session'));
    $session->shouldReceive('put')->once();

    $provider = new LinearProvider(
        $request,
        'client-id',
        'client-secret',
        'http://localhost/callback'
    );

    $url = $provider->redirect()->getTargetUrl();

    expect($url)->toContain('https://linear.app/oauth/authorize')
        ->and($url)->toContain('client_id=client-id')
        ->and($url)->toContain('redirect_uri=http%3A%2F%2Flocalhost%2Fcallback')
        ->and($url)->toContain('scope=read');
});

it('maps user data correctly', function () {
    $provider = new LinearProvider(
        Request::create('http://localhost'),
        'client-id',
        'client-secret',
        'http://localhost/callback'
    );

    $user = [
        'id' => '12345',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatarUrl' => 'https://example.com/avatar.jpg',
    ];

    // Use reflection to test the protected mapUserToObject method
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('mapUserToObject');
    $method->setAccessible(true);

    $result = $method->invoke($provider, $user);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->getId())->toBe('12345')
        ->and($result->getName())->toBe('John Doe')
        ->and($result->getEmail())->toBe('john@example.com')
        ->and($result->getAvatar())->toBe('https://example.com/avatar.jpg');
});

it('can customize requested user fields', function () {
    $request = Request::create('http://localhost');
    $request->setLaravelSession($session = m::mock('Illuminate\Contracts\Session\Session'));
    $session->shouldReceive('put')->once();

    $provider = new LinearProvider(
        $request,
        'client-id',
        'client-secret',
        'http://localhost/callback'
    );

    $provider->fields(['id', 'name', 'email', 'admin', 'active']);

    $url = $provider->redirect()->getTargetUrl();

    expect($url)->toContain('https://linear.app/oauth/authorize');
});

it('handles missing user data gracefully', function () {
    $provider = new LinearProvider(
        Request::create('http://localhost'),
        'client-id',
        'client-secret',
        'http://localhost/callback'
    );

    // Simulate missing fields
    $user = [
        'id' => '12345',
    ];

    // Use reflection to test the protected mapUserToObject method
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('mapUserToObject');
    $method->setAccessible(true);

    $result = $method->invoke($provider, $user);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->getId())->toBe('12345')
        ->and($result->getName())->toBeNull()
        ->and($result->getEmail())->toBeNull()
        ->and($result->getAvatar())->toBeNull();
});
