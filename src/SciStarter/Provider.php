<?php

namespace SocialiteProviders\SciStarter;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'SCISTARTER';

    protected $scopeSeparator = ' ';

    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://scistarter.org/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://scistarter.org/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://scistarter.org/userinfo', [
            RequestOptions::QUERY => [
                'prettyPrint' => 'false',
            ],
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer $token",
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'                => Arr::get($user, 'sub'),
            'nickname'          => Arr::get($user, 'nickname'),
            'name'              => Arr::get($user, 'name'),
            'email'             => Arr::get($user, 'email'),
            'avatar'            => $avatarUrl = Arr::get($user, 'picture'),
            'avatar_original'   => $avatarUrl,
        ]);
    }
}
