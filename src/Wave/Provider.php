<?php

namespace SocialiteProviders\Wave;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WAVE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user:read'];

    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://api.waveapps.com/oauth2/authorize', $state);
    }

    protected function getQueryUrl()
    {
        return 'https://gql.waveapps.com/graphql/public';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.waveapps.com/oauth2/token/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->getQueryUrl(), [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::JSON => [
                'query' => 'query { user {id firstName lastName defaultEmail} }',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $firstName = data_get($user, 'data.user.firstName');
        $lastName = data_get($user, 'data.user.lastName');

        return (new User())->setRaw($user)->map([
            'id'          => data_get($user, 'data.user.id'),
            'name'        => "{$firstName} {$lastName}",
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'email'       => data_get($user, 'data.user.defaultEmail'),
            'business_id' => data_get($this->credentialsResponseBody, 'businessId'),
        ]);
    }
}
