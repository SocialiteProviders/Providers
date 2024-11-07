<?php

namespace SocialiteProviders\PlanningCenter;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'PLANNINGCENTER';

    protected $scopes = ['people'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.planningcenteronline.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.planningcenteronline.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.planningcenteronline.com/people/v2/me', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::QUERY => [
                'include' => 'emails',
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
            'id'       => $user['data']['id'],
            'nickname' => $user['data']['attributes']['nickname'],
            'name'     => $user['data']['attributes']['name'],
            'email'    => $user['included'][0]['attributes']['address'],
            'avatar'   => $user['data']['attributes']['avatar'],
        ]);
    }
}
