<?php

namespace SocialiteProviders\StackExchange;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * https://api.stackexchange.com/docs/authentication
 * Class Provider.
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'STACKEXCHANGE';

    protected $version = '2.2';

    protected function getAuthUrl($state): string
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

    protected function getTokenUrl(): string
    {
        return 'https://stackexchange.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        parse_str((string) $response->getBody(), $data);

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
                    'site'         => $this->getConfig('site'),
                    'access_token' => $token,
                    'key'          => $this->getConfig('key'),
                ]
            ),
            [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['site', 'key'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map(
            [
                'id'       => $user['items'][0]['account_id'],
                'nickname' => $user['items'][0]['display_name'],
                'name'     => $user['items'][0]['display_name'],
                'avatar'   => $user['items'][0]['profile_image'],
            ]
        );
    }
}
