<?php

namespace SocialiteProviders\Snapchat;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'SNAPCHAT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'https://auth.snapchat.com/oauth2/api/user.display_name',
        'https://auth.snapchat.com/oauth2/api/user.bitmoji.avatar',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://accounts.snapchat.com/accounts/oauth2/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.snapchat.com/accounts/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://kit.snapchat.com/v1/me?', [
            RequestOptions::QUERY => [
                RequestOptions::QUERY => '{me{externalId displayName bitmoji{avatar id}}}',
            ],
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
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'data.me.externalId'),
            'name'     => Arr::get($user, 'data.me.displayName'),
            'avatar'   => Arr::get($user, 'data.me.bitmoji.avatar'),
        ]);
    }
}
