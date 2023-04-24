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
    protected $scopes = [];

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
                'https://developer.api.autodesk.com/authentication/v1/authorize',
                $state
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://developer.api.autodesk.com/authentication/v1/gettoken';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code): array
    {
        return parent::getTokenFields($code);
    }

    /**
     * @param string $token
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            'https://developer.api.autodesk.com/userprofile/v1/users/@me',
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
     * https://forge.autodesk.com/en/docs/oauth/v2/reference/http/users-@me-GET/.
     *
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user): \SocialiteProviders\Manager\OAuth2\User
    {
        return (new User())->setRaw($user)->map([
            'id'             => $user['userId'],
            'email'          => $user['emailId'],
            'username'       => $user['userName'],
            'first_name'     => $user['firstName'],
            'last_name'      => $user['lastName'],
            'country_code'   => $user['countryCode'],
            'language'       => $user['language'],
            'profile_images' => $user['profileImages'],
            'website'        => $user['websiteUrl'] ?? null,
        ]);
    }
}
