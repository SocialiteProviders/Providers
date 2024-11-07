<?php

namespace SocialiteProviders\Whmcs;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class Provider.
 *
 * @see https://docs.whmcs.com/OpenID_Connect_Developer_Guide
 */
class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'WHMCS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid', 'email', 'profile'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    public static function additionalConfigKeys(): array
    {
        return ['url'];
    }

    /**
     * @return array OpenID data for WHMCS
     */
    protected function getOpenidConfig()
    {
        static $data = null;

        if ($data === null) {
            $configUrl = $this->getConfig('url').'/oauth/openid-configuration.php';

            $response = $this->getHttpClient()->get($configUrl);

            $data = json_decode((string) $response->getBody(), true);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getOpenidConfig()['authorization_endpoint'],
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        return $url.'?'.http_build_query(
            [
                'client_id'     => $this->clientId,
                'redirect_uri'  => $this->redirectUrl,
                'response_type' => 'code',
                'scope'         => $this->formatScopes($this->scopes, $this->scopeSeparator),
                'state'         => $state,
            ],
            '',
            '&',
            $this->encodingType
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOpenidConfig()['token_endpoint'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => array_merge(
                $this->getTokenFields($code),
                [
                    'grant_type' => 'authorization_code',
                ]
            ),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Get the user_info URL for the provider.
     *
     * @return string
     */
    protected function getUserInfoUrl()
    {
        return $this->getOpenidConfig()['userinfo_endpoint'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getUserInfoUrl(), [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::QUERY => [
                'access_token' => $token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map(
            [
                'avatar'   => null,
                'email'    => $user['email'],
                'id'       => $user['sub'],
                'name'     => $user['name'],
                'nickname' => null,
            ]
        );
    }
}
