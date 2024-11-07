<?php

namespace SocialiteProviders\Fitbit;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'FITBIT';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['profile'];

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://fitbit.com/oauth2/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.fitbit.com/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS      => ['Accept' => 'application/json', 'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],
            RequestOptions::FORM_PARAMS  => $this->getTokenFields($code),
        ]);

        return $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.fitbit.com/1/user/-/profile.json', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
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
            'id'       => $user['user']['encodedId'],
            'nickname' => $user['user']['nickname'] ?? '',
            'name'     => $user['user']['fullName'],
            'email'    => null,
            'avatar'   => $user['user']['avatar150'],
        ]);
    }
}
