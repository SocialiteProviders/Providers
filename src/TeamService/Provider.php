<?php

namespace SocialiteProviders\TeamService;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'TEAMSERVICE';

    protected $encodingType = PHP_QUERY_RFC3986;

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://app.vssps.visualstudio.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://app.vssps.visualstudio.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $uri = 'https://'.$this->getAccount().'.vssps.visualstudio.com/_apis/profile/profiles/me';

        $response = $this->getHttpClient()->get($uri, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'api-version' => '5.0-preview.3',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['displayName'],
            'name'     => $user['displayName'],
            'email'    => $user['emailAddress'],
            'avatar'   => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'grant_type'            => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion'      => $this->clientSecret,
            'assertion'             => $code,
            'redirect_uri'          => $this->redirectUrl,
        ];
    }

    /**
     * Load the specified microsoft account.
     *
     * @return string
     */
    protected function getAccount()
    {
        return $this->getConfig('account', 'app');
    }

    public static function additionalConfigKeys(): array
    {
        return ['account'];
    }
}
