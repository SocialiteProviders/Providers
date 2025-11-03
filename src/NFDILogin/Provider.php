<?php

namespace SocialiteProviders\NfdiLogin;

use Exception;
use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * @see https://nfdi-aai.de/infraproxy
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'NFDILOGIN';

    protected $scopes = ['openid', 'email'];

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        // Remove client ID and secret because they are sent in the Authorization header.
        $fields = parent::getTokenFields($code);
        unset($fields['client_id']);
        unset($fields['client_secret']);

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenHeaders($code)
    {
        $headers = parent::getTokenHeaders($code);
        $headers['Authorization'] = 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret);

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase('https://infraproxy.nfdi-aai.dfn.de/idp/profile/oidc/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return 'https://infraproxy.nfdi-aai.dfn.de/idp/profile/oidc/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://infraproxy.nfdi-aai.dfn.de/idp/profile/oidc/userinfo', [
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
            'id'          => $user['sub'],
            'name'        => $user['name'] ?? '',
            'given_name'  => $user['given_name'],
            'family_name' => $user['family_name'],
            'email'       => $user['email'],
        ]);
    }
}
