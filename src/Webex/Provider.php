<?php

namespace SocialiteProviders\Webex;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'WEBEX';

    /**
     * The Webex REST API origin URL.
     *
     * @var string
     */
    protected $originUrl = 'https://webexapis.com';

    /**
     * The Webex API version.
     *
     * @var string
     */
    protected $version = 'v1';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['spark:people_read', 'spark:kms'];

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
        return $this->buildAuthUrlFromBase($this->originUrl.'/'.$this->version.'/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->originUrl.'/'.$this->version.'/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $url = $this->originUrl.'/'.$this->version.'/people/me?callingData=true';

        $response = $this->getHttpClient()->get($url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
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
            'nickname'   => ! empty($user['nickName']) ? $user['nickName'] : null,
            'name'       => ! empty($user['displayName']) ? $user['displayName'] : null,
            'first_name' => ! empty($user['firstName']) ? $user['firstName'] : null,
            'last_name'  => ! empty($user['lastName']) ? $user['lastName'] : null,
            'email'      => $user['emails'][0],
            'avatar'     => ! empty($user['avatar']) ? $user['avatar'] : null,
        ]);
    }
}
