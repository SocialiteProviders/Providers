<?php

namespace SocialiteProviders\HubSpot;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://legacydocs.hubspot.com/docs/methods/oauth2/oauth2-overview
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'HUBSPOT';

    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://app.hubspot.com/oauth/authorize', $state);
    }

    protected function getTokenUrl(): string
    {
        return 'https://api.hubapi.com/oauth/v1/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.hubspot.com/oauth/v1/access-tokens/'.$token);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'nickname' => null,
            'name'     => null,
            'email'    => $user['user'],
            'avatar'   => null,
            'id'       => $user['user_id'],
        ]);
    }

    /**
     * Acquire a new access token using the refresh token.
     *
     * @see https://developers.hubspot.com/docs/api/oauth-quickstart-guide#refreshing_tokens
     *
     * @param  string  $refreshToken
     * @return array
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::FORM_PARAMS => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }
}
