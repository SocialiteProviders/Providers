<?php

namespace SocialiteProviders\DocuSign;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'DOCUSIGN';

    protected $usesPKCE = true;

    protected $scopes = ['signature'];

    /**
     * {@inheritDoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://account-d.docusign.com/oauth/auth', $state);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTokenUrl()
    {
        return 'https://account-d.docusign.com/oauth/token';
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret)],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://account-d.docusign.com/oauth/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user);
    }
}
