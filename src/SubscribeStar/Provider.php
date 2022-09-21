<?php

namespace SocialiteProviders\Subscribestar;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'SUBSCRIBESTAR';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user.read', 'user.email.read', 'subscriber.read'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.subscribestar.com/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.subscribestar.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $query = '{
            user {
                id
                email
                name
                avatar_url
            }
        }
        ';

        $queryParams = [
            'query' => $query,
        ];
        $response = $this->getHttpClient()->post(
            'https://www.subscribestar.com/api/graphql/v1',
            [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::FORM_PARAMS => $queryParams,
            ]
        );
        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['data']['user']['id'],
            'name'     => $user['data']['user']['name'],
            'email'    => $user['data']['user']['email'],
            'avatar'   => $user['data']['user']['avatar_url'],
        ]);
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


}
