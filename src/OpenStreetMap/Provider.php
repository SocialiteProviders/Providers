<?php

namespace SocialiteProviders\OpenStreetMap;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OPENSTREETMAP';

    protected $scopes = ['read_prefs'];

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://www.openstreetmap.org/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://www.openstreetmap.org/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.openstreetmap.org/api/0.6/user/details.json',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        $json = json_decode((string) $response->getBody(), true);

        return $json['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['display_name'],
            'name'     => $user['display_name'],
            'email'    => null,
            'avatar'   => Arr::get($user, 'img.href'),
        ]);
    }
}
