<?php

namespace SocialiteProviders\MusicBrainz;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MUSICBRAINZ';

    protected $scopes = ['profile'];

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $usesPKCE = true;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://musicbrainz.org/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://musicbrainz.org/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://musicbrainz.org/oauth2/userinfo',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer $token",
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
            'id'   => $user['metabrainz_user_id'],
            'name' => $user['sub'],
        ]);
    }
}
