<?php

namespace SocialiteProviders\Webex;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WEBEX';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['spark:kms', 'spark:people_read'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://webexapis.com/v1/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://webexapis.com/v1/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://webexapis.com/v1/people/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
            ],
            RequestOptions::QUERY => [
                'callingData'        => 'true',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'         => $user['id'],
            'nickname'   => $user['nickName'] ?: null,
            'name'       => $user['displayName'] ?: null,
            'first_name' => $user['firstName'] ?: null,
            'last_name'  => $user['lastName'] ?: null,
            'email'      => $user['emails'][0],
            'avatar'     => $user['avatar'] ?: null,
        ]);
    }
}
