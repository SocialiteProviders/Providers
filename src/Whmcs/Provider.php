<?php

namespace SocialiteProviders\Whmcs;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

/**
 * Class Provider.
 *
 * @see https://docs.whmcs.com/OpenID_Connect_Developer_Guide
 */
class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'WHMCS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid', 'email', 'profile'];
    
    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return [
            'url',
        ];
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

            $data = json_decode($response->getBody()->getContents(), true);
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
            'headers'     => ['Accept' => 'application/json'],
            'form_params' => array_merge(
                $this->getTokenFields($code),
                [
                    'grant_type' => 'authorization_code',
                ]
            ),
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
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
        $response = $this->getHttpClient()->get(
            $this->getUserInfoUrl().'?'.http_build_query(
                [
                    'access_token' => $token,
                ]
            ),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $arrayKey
     */
    protected function getFromConfig($arrayKey)
    {
        return app()['config']['services.whmcs'][$arrayKey];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
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
