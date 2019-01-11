<?php

namespace SocialiteProviders\Untappd;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'UNTAPPD';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://untappd.com/oauth/authenticate/', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://untappd.com/oauth/authorize/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.untappd.com/v4/user/info?access_token='.$token, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return array_get(json_decode($response->getBody()->getContents(), true), 'response.user');
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'              => $user['id'],
            'nickname'        => $user['user_name'],
            'name'            => array_get($user, 'first_name').' '.array_get($user, 'last_name'),
            'email'           => array_get($user, 'settings.email_address'),
            'avatar'          => array_get($user, 'user_avatar'),
            'avatar_original' => array_get($user, 'user_avatar_hd'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'response_type' => 'code',
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     *
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'query'   => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }
}
