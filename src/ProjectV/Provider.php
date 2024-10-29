<?php

namespace SocialiteProviders\ProjectV;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PROJECTV';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'https://v.enl.one/oauth/api/v1/email',
        'https://v.enl.one/oauth/api/v1/googledata',
        'https://v.enl.one/oauth/api/v1/userinfo',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://v.enl.one/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://v.enl.one/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://v.enl.one/oauth/verify',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'gid'         => Arr::get($user, 'data.gid'),               //googledata
            'vid'         => Arr::get($user, 'data.enlid'),             //profile
            'nickname'    => Arr::get($user, 'data.agent'),             //profile
            'forename'    => Arr::get($user, 'data.forename'),          //googledata
            'lastname'    => Arr::get($user, 'data.lastname'),          //googledata
            'avatarurl'   => Arr::get($user, 'data.imageurl'),          //googledata
            'email'       => Arr::get($user, 'data.email'),             //email
            'vlevel'      => Arr::get($user, 'data.vlevel'),            //profile
            'vpoints'     => Arr::get($user, 'data.vpoints'),           //profile
            'quarantine'  => Arr::get($user, 'data.quarantine'),        //profile
            'active'      => Arr::get($user, 'data.active'),            //profile
            'blacklisted' => Arr::get($user, 'data.blacklisted'),       //profile
            'verified'    => Arr::get($user, 'data.verified'),          //profile
            'flagged'     => Arr::get($user, 'data.flagged'),           //profile
        ]);
    }
}
