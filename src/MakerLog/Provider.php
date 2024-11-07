<?php

namespace SocialiteProviders\MakerLog;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'MAKERLOG';

    protected $scopes = ['user:read user:email'];

    protected $scopeSeparator = ' ';

    /**
     * return the api base url.
     *
     * @return string
     */
    protected function baseUrl()
    {
        return 'https://api.getmakerlog.com';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->baseUrl().'/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->baseUrl().'/oauth/token/';
    }

    protected function getEmailUrl()
    {
        return $this->baseUrl().'/accounts/read_email/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->baseUrl().'/me/', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'format' => 'json',
            ],
        ]);

        // parse the response and add the email address in.
        $result = json_decode((string) $response->getBody(), true);
        $result['email'] = $this->getEmailByToken($token);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['first_name'].' '.$user['last_name'],
            'avatar'   => $user['avatar'],
        ]);
    }

    /**
     *  Get the account email of the current user.
     *
     * @param  string  $token
     * @return string
     */
    protected function getEmailByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getEmailUrl(),
            [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode((string) $response->getBody(), true)['email'];
    }
}
