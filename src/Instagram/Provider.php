<?php

namespace SocialiteProviders\Instagram;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'INSTAGRAM';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['account_type', 'id', 'username', 'media_count'];

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['user_profile'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://api.instagram.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.instagram.com/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $queryParameters = [
            'access_token' => $token,
            'fields'       => implode(',', $this->fields),
        ];

        if (! empty($this->clientSecret)) {
            $queryParameters['appsecret_proof'] = hash_hmac('sha256', $token, $this->clientSecret);
        }

        $response = $this->getHttpClient()->get('https://graph.instagram.com/me', [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::QUERY => $queryParameters,
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'            => $user['id'],
            'name'          => $user['username'],
            'account_type'  => $user['account_type'],
            'media_count'   => $user['media_count'] ?? null,
        ]);
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }
}
