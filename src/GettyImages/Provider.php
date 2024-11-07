<?php

namespace SocialiteProviders\GettyImages;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'GETTYIMAGES';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://api.gettyimages.com/oauth2/auth/', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.gettyimages.com/oauth2/token';
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post(
            $this->getTokenUrl(),
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Basic '.base64_encode(
                        $this->clientId.':'.$this->clientSecret
                    ),
                ],
                RequestOptions::BODY    => $this->getTokenFields($code),
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.gettyimages.com/v3/customers/current',
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                    'Api-Key'       => $this->clientId,
                ], ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
        ]);
    }
}
