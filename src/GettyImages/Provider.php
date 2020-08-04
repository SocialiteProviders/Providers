<?php

namespace SocialiteProviders\GettyImages;

use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'GETTYIMAGES';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.gettyimages.com/oauth2/auth/',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.gettyimages.com/oauth2/token';
    }

    public function getAccessToken($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post(
            $this->getTokenUrl(),
            [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode(
                        $this->clientId.':'.$this->clientSecret
                    ),
                ],
                $postKey    => $this->getTokenFields($code),
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.gettyimages.com/v3/customers/current',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Api-Key'       => $this->clientId,
                ], ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
        ]);
    }
}
