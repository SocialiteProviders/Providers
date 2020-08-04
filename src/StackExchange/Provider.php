<?php

namespace SocialiteProviders\StackExchange;

use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * https://api.stackexchange.com/docs/authentication
 * Class Provider.
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'STACKEXCHANGE';

    protected $version = '2.2';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://stackexchange.com/oauth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        // https://api.stackexchange.com/docs/authentication

        return $url.'?'.http_build_query(
            [
                'client_id'    => $this->clientId,
                'redirect_uri' => $this->redirectUrl,
                'scope'        => $this->formatScopes($this->scopes, $this->scopeSeparator),
                'state'        => $state,
            ],
            '',
            '&',
            $this->encodingType
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://stackexchange.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers'     => ['Accept' => 'application/json'],
            $postKey => $this->getTokenFields($code),
        ]);

        parse_str($response->getBody()->getContents(), $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // https://api.stackexchange.com/docs/me
        $response = $this->getHttpClient()->get(
            'https://api.stackexchange.com/'.$this->version.
            '/me?'.http_build_query(
                [
                    'site'         => $this->getFromConfig('site'),
                    'access_token' => $token,
                    'key'          => $this->getFromConfig('key'),
                ]
            ),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $arrayKey
     */
    protected function getFromConfig($arrayKey)
    {
        return app()['config']['services.stackexchange'][$arrayKey];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'id'       => $user['items'][0]['account_id'],
                'nickname' => $user['items'][0]['display_name'],
                'name'     => $user['items'][0]['display_name'],
                'avatar'   => $user['items'][0]['profile_image'],
            ]
        );
    }
}
