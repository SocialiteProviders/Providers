<?php

namespace SocialiteProviders\MakerLog;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'makerlog';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user:read user:email'];

    /**
     * {@inheritdoc}
     */
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
        // get the user
        $response = $this->getHttpClient()->get(
            $this->baseUrl().'/me/?format=json',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        // parse the response and add the email address in.
        $result = json_decode($response->getBody()->getContents(), true);
        $result['email'] = $this->getEmailByToken($token);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['first_name'].' '.$user['last_name'],
            'avatar'   => $user['avatar'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(
            parent::getTokenFields($code),
            [
                'grant_type' => 'authorization_code',
            ]
        );
    }

    /**
     *  Get the account email of the current user.
     *
     * @param string $token
     *
     * @return string
     */
    protected function getEmailByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getEmailUrl(),
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true)['email'];
    }
}
