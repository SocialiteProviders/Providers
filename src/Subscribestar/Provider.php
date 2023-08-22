<?php

namespace SocialiteProviders\Subscribestar;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://www.subscribestar.com/api
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'SUBSCRIBESTAR';

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

        $response = $this->getHttpClient()->post(
            'https://www.subscribestar.com/api/graphql/v1',
            [
                RequestOptions::HEADERS => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::FORM_PARAMS => ['query' => $query],
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
            'id'       => $user['data']['user']['id'],
            'name'     => $user['data']['user']['name'] ?? null,
            'email'    => $user['data']['user']['email'] ?? null,
            'avatar'   => $user['data']['user']['avatar_url'] ?? null,
        ]);
    }
}
