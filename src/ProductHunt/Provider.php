<?php

namespace SocialiteProviders\ProductHunt;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PRODUCTHUNT';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['public', 'private'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.producthunt.com/v2/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.producthunt.com/v2/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post(
            'https://api.producthunt.com/v2/api/graphql',
            [
                RequestOptions::HEADERS => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                RequestOptions::JSON => [
                    'query' => '{
                            viewer {
                                user {
                                    id
                                    name
                                    profileImage
                                    username
                                }
                            }
                        }',
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
        $user = $user['data']['viewer']['user'] ?? [];
        $avatar = $user['profileImage'] ?? null;

        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['name'],
            'avatar'   => $avatar,
        ]);
    }
}
