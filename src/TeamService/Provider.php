<?php

namespace SocialiteProviders\TeamService;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
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
        $response = $this->getHttpClient()->get(
            'https://'.$this->getAccount().'.vssps.visualstudio.com/_apis/profile/profiles/me?api-version=5.0-preview.3',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
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
        return (new User())->setRaw($user)->map([
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

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['account'];
    }
}
