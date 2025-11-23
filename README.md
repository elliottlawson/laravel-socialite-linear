# Laravel Socialite Linear Provider

A Laravel Socialite provider for authenticating with [Linear](https://linear.app) using OAuth 2.0.

## Installation

You can install the package via composer:

```bash
composer require elliottlawson/laravel-socialite-linear
```

## Configuration

### 1. Create a Linear OAuth Application

First, you'll need to create an OAuth application in Linear:

1. Go to your Linear workspace settings
2. Navigate to the "OAuth Applications" section
3. Click "New OAuth Application"
4. Enter your application details:
   - **Name**: Your application name
   - **Callback URL**: `https://yourdomain.com/auth/linear/callback`
5. Copy your **Client ID** and **Client Secret**

### 2. Add Credentials to Laravel

Add your Linear OAuth credentials to your `config/services.php` file:

```php
'linear' => [
    'client_id' => env('LINEAR_CLIENT_ID'),
    'client_secret' => env('LINEAR_CLIENT_SECRET'),
    'redirect' => env('LINEAR_REDIRECT_URI'),
],
```

Then add the following to your `.env` file:

```env
LINEAR_CLIENT_ID=your-client-id
LINEAR_CLIENT_SECRET=your-client-secret
LINEAR_REDIRECT_URI=https://yourdomain.com/auth/linear/callback
```

## Usage

### Basic Authentication

Use Socialite's typical workflow for OAuth authentication:

```php
use Laravel\Socialite\Facades\Socialite;

// Redirect to Linear for authentication
Route::get('/auth/linear', function () {
    return Socialite::driver('linear')->redirect();
});

// Handle the callback from Linear
Route::get('/auth/linear/callback', function () {
    $user = Socialite::driver('linear')->user();

    // $user->token - OAuth access token
    // $user->refreshToken - Refresh token (if provided)
    // $user->expiresIn - Token expiration time
    // $user->getId() - Linear user ID
    // $user->getName() - User's name
    // $user->getEmail() - User's email
    // $user->getAvatar() - User's avatar URL

    // Create or update user in your database
    // Store the access token for API calls

    return redirect('/dashboard');
});
```

### Requesting Scopes

Linear supports the following OAuth scopes:

- `read` (required - always included by default)
- `write` - Write access to the user's Linear data

To request additional scopes:

```php
return Socialite::driver('linear')
    ->scopes(['read', 'write'])
    ->redirect();
```

### Customizing User Fields

By default, the provider requests `id`, `name`, `email`, and `avatarUrl` from Linear's API. You can customize which fields to retrieve:

```php
return Socialite::driver('linear')
    ->fields(['id', 'name', 'email', 'avatarUrl', 'admin', 'active', 'timezone'])
    ->redirect();
```

Any valid Linear GraphQL viewer fields are supported. See [Linear's API documentation](https://studio.apollographql.com/public/Linear-API/variant/current/home) for available fields.

### Using the Access Token

After authentication, you can use the access token to make API calls to Linear. The token is available on the user object:

```php
$user = Socialite::driver('linear')->user();
$accessToken = $user->token;

// Use this token with a Linear API client
// For example, with the GLHD Linear package:
$linear = new Linear\Client($accessToken);
```

### Token Refresh

Linear access tokens expire after 24 hours. Refresh tokens are provided by default for OAuth applications created after October 1, 2025. When you authenticate a user, you'll receive both tokens:

```php
$user = Socialite::driver('linear')->user();

$accessToken = $user->token;
$refreshToken = $user->refreshToken; // Store this securely
$expiresIn = $user->expiresIn; // 86400 (24 hours)
```

To refresh an expired access token:

```php
use Laravel\Socialite\Facades\Socialite;

$provider = Socialite::driver('linear');
$newToken = $provider->refreshToken($storedRefreshToken);

// Access the new tokens
$newAccessToken = $newToken->token;
$newRefreshToken = $newToken->refreshToken;
$expiresIn = $newToken->expiresIn;
```

### Stateless Authentication

For API-based authentication without sessions:

```php
return Socialite::driver('linear')->stateless()->user();
```

## User Data

After authentication, the user object contains the following data from Linear:

```php
[
    'id' => '12345',                              // Linear user ID
    'name' => 'John Doe',                        // User's name
    'email' => 'john@example.com',               // User's email
    'avatar' => 'https://example.com/avatar.jpg', // Avatar URL (may be null)
]
```

The raw user data from Linear's GraphQL API is also available via `$user->getRaw()`.

## Testing

```bash
composer test
```

## Code Quality

Run PHPStan:

```bash
composer analyse
```

Check code style:

```bash
composer format
```

## Requirements

- PHP 8.2 or higher
- Laravel 10, 11, or 12
- Laravel Socialite 5.x

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
