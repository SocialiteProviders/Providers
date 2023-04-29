<?php

namespace SocialiteProviders\Azure;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'AZURE';

    /**
     * The base Azure Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://graph.microsoft.com/v1.0/me';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['User.Read'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl().'/oauth2/v2.0/authorize', $state);
    }

    /**
     * Return the logout endpoint with post_logout_redirect_uri query parameter.
     *
     * @param string $redirectUri
     *
     * @return string
     */
    public function getLogoutUrl(string $redirectUri)
    {
        return $this->getBaseUrl()
            .'/oauth2/logout?'
            .http_build_query(['post_logout_redirect_uri' => $redirectUri], '', '&', $this->encodingType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseUrl().'/oauth2/v2.0/token';
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string) $response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->graphUrl, [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            RequestOptions::PROXY => $this->getConfig('proxy'),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'            => $user['id'],
            'nickname'      => null,
            'name'          => $user['displayName'],
            'email'         => $user['userPrincipalName'],
            'principalName' => $user['userPrincipalName'],
            'mail'          => $user['mail'],
            'avatar'        => null,
        ]);
    }

    /**
     * Get the access token response for the given code.
     *
     * @param string $code
     *
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
            RequestOptions::PROXY       => $this->getConfig('proxy'),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return 'https://' . $this->getConfig('host', 'login.microsoftonline.com') . '/'.$this->getConfig('tenant', 'common');
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return ['tenant', 'proxy'];
    }
}
