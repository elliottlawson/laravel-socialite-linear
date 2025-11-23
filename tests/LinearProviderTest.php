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
    $provider = new LinearProvider(
        Request::create('http://localhost'),
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

it('returns a user instance for the authenticated request', function () {
    $request = Request::create('http://localhost', 'GET', ['code' => 'code', 'state' => 'state']);
    $request->setLaravelSession($session = m::mock('Illuminate\Contracts\Session\Session'));
    $session->shouldReceive('pull')->once()->with('state')->andReturn('state');

    $provider = new LinearProvider($request, 'client-id', 'client-secret', 'redirect-uri');

    $provider = m::mock(LinearProvider::class, [$request, 'client-id', 'client-secret', 'redirect-uri'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $token = m::mock('Laravel\Socialite\Two\AccessTokenResponse');
    $token->shouldReceive('offsetGet')->with('access_token')->andReturn('access-token');
    $token->shouldReceive('offsetGet')->with('refresh_token')->andReturn('refresh-token');
    $token->shouldReceive('offsetGet')->with('expires_in')->andReturn(3600);

    $provider->shouldReceive('getAccessTokenResponse')->once()->andReturn($token);

    $user = [
        'id' => '12345',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatarUrl' => 'https://example.com/avatar.jpg',
    ];

    $provider->shouldReceive('getUserByToken')->once()->with('access-token')->andReturn($user);

    $result = $provider->user();

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->getId())->toBe('12345')
        ->and($result->getName())->toBe('John Doe')
        ->and($result->getEmail())->toBe('john@example.com')
        ->and($result->getAvatar())->toBe('https://example.com/avatar.jpg')
        ->and($result->token)->toBe('access-token')
        ->and($result->refreshToken)->toBe('refresh-token')
        ->and($result->expiresIn)->toBe(3600);
});

it('can customize requested user fields', function () {
    $provider = new LinearProvider(
        Request::create('http://localhost'),
        'client-id',
        'client-secret',
        'http://localhost/callback'
    );

    $provider->fields(['id', 'name', 'email', 'admin', 'active']);

    $url = $provider->redirect()->getTargetUrl();

    expect($url)->toContain('https://linear.app/oauth/authorize');
});

it('handles missing user data gracefully', function () {
    $request = Request::create('http://localhost', 'GET', ['code' => 'code', 'state' => 'state']);
    $request->setLaravelSession($session = m::mock('Illuminate\Contracts\Session\Session'));
    $session->shouldReceive('pull')->once()->with('state')->andReturn('state');

    $provider = m::mock(LinearProvider::class, [$request, 'client-id', 'client-secret', 'redirect-uri'])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $token = m::mock('Laravel\Socialite\Two\AccessTokenResponse');
    $token->shouldReceive('offsetGet')->with('access_token')->andReturn('access-token');
    $token->shouldReceive('offsetGet')->with('refresh_token')->andReturn(null);
    $token->shouldReceive('offsetGet')->with('expires_in')->andReturn(null);

    $provider->shouldReceive('getAccessTokenResponse')->once()->andReturn($token);

    // Simulate missing fields
    $user = [
        'id' => '12345',
    ];

    $provider->shouldReceive('getUserByToken')->once()->with('access-token')->andReturn($user);

    $result = $provider->user();

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->getId())->toBe('12345')
        ->and($result->getName())->toBeNull()
        ->and($result->getEmail())->toBeNull()
        ->and($result->getAvatar())->toBeNull();
});
