<?php

namespace SocialiteProviders\AutodeskAPS;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AUTODESKAPS';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return
            $this->buildAuthUrlFromBase(
                'https://developer.api.autodesk.com/authentication/v2/authorize',
                $state
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://developer.api.autodesk.com/authentication/v2/token';
    }

    /**
     * @param  string  $token
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            'https://api.userprofile.autodesk.com/userinfo',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return (array) json_decode((string) $response->getBody(), true);
    }

    /**
     * @see https://aps.autodesk.com/en/docs/oauth/v2/reference/http/userinfo-GET/.
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id'             => $user['sub'],
            'email'          => $user['email'],
            'email_verified' => $user['email_verified'],
            'username'       => $user['preferred_username'],
            'full_name'      => $user['name'],
            'first_name'     => $user['given_name'],
            'last_name'      => $user['family_name'],
            'language'       => $user['locale'],
            'image'          => $user['picture'],
            'website'        => $user['profile'] ?? null,
        ]);
    }
}
