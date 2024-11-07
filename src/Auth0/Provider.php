<?php

namespace SocialiteProviders\Auth0;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AUTH0';

    protected $scopes = [
        'openid',
        'profile',
        'email',
    ];

    protected $scopeSeparator = ' ';

    protected function getAuth0Url()
    {
        return $this->getConfig('base_url');
    }

    public static function additionalConfigKeys(): array
    {
        return ['base_url'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getAuth0Url().'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getAuth0Url().'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getAuth0Url().'/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
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
            'id'       => $user['sub'],
            'nickname' => $user['nickname'],
            'name'     => Arr::get($user, 'given_name', '').' '.Arr::get($user, 'family_name', ''),
            'email'    => Arr::get($user, 'email'),
            'avatar'   => null,
        ]);
    }
}
