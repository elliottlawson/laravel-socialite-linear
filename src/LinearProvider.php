<?php

namespace ElliottLawson\SocialiteLinear;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\Token;
use Laravel\Socialite\Two\User;

class LinearProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array<int, string>
     */
    protected $scopes = ['read'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ',';

    /**
     * The user fields being requested.
     *
     * @var array<int, string>
     */
    protected $fields = ['id', 'name', 'email', 'avatarUrl'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://linear.app/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.linear.app/oauth/token';
    }

    /**
     * Get the user instance for the authenticated user.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        try {
            $response = $this->getHttpClient()->post(
                'https://api.linear.app/graphql',
                $this->getRequestOptions($token)
            );

            $data = json_decode((string) $response->getBody(), true);

            if (! isset($data['errors'])) {
                return Arr::get($data, 'data.viewer', []);
            }
        } catch (Exception $e) {
            // Fall through to return empty array
        }

        return [];
    }

    /**
     * Get the request options for the Linear GraphQL API.
     *
     * @param  string  $token
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => [
                'query' => $this->getGraphQLQuery(),
            ],
        ];
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'id'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'avatarUrl'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * @param  string  $refreshToken
     * @return \Laravel\Socialite\Two\Token
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getRefreshTokenResponse($refreshToken);

        return new Token(
            Arr::get($response, 'access_token'),
            Arr::get($response, 'refresh_token', $refreshToken),
            Arr::get($response, 'expires_in'),
            explode($this->scopeSeparator, Arr::get($response, 'scope', ''))
        );
    }

    /**
     * Set the user fields to request from Linear.
     *
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Get the GraphQL query for retrieving user data.
     *
     * @return string
     */
    protected function getGraphQLQuery()
    {
        $fields = implode(' ', $this->fields);

        return "{ viewer { {$fields} } }";
    }
}
