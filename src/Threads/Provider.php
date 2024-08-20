<?php

namespace SocialiteProviders\Threads;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://developers.facebook.com/docs/threads
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'THREADS';

    /**
     * The user fields being requested.
     *
     * @var array<string>
     */
    protected $fields = [
        'id',
        'threads_profile_picture_url',
        'username',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'threads_basic',
    ];

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://threads.net/oauth/authorize', $state);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        return 'https://graph.threads.net/oauth/access_token';
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token)
    {
        $queryParameters = [
            'access_token' => $token,
            'fields'       => implode(',', $this->fields),
        ];

        if (! empty($this->clientSecret)) {
            $queryParameters['appsecret_proof'] = hash_hmac('sha256', $token, $this->clientSecret);
        }

        $response = $this->getHttpClient()->get('https://graph.threads.net/me', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::QUERY => $queryParameters,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'           => Arr::get($user, 'id'),
            'nickname'     => Arr::get($user, 'username'),
            'name'         => null,
            'email'        => null,
            'avatar'       => Arr::get($user, 'threads_profile_picture_url') ?? null,
        ]);
    }
}
