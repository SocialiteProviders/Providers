<?php

namespace SocialiteProviders\Foursquare;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FOURSQUARE';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://foursquare.com/oauth2/authenticate', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://foursquare.com/oauth2/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.foursquare.com/v2/users/self', [
            RequestOptions::QUERY => [
                'oauth_token' => $token,
                'v'           => '20150214',
            ],
        ]);

        return json_decode((string) $response->getBody(), true)['response']['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'     => $user['id'], 'nickname' => null,
            'name'   => Arr::get($user, 'firstName').' '.Arr::get($user, 'lastName'),
            'email'  => $user['contact']['email'],
            'avatar' => $user['photo']['prefix'].'original'.$user['photo']['suffix'],
        ]);
    }
}
