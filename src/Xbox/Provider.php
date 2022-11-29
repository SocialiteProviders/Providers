<?php

namespace SocialiteProviders\Xbox;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'XBOX';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['XboxLive.signin'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://login.live.com/oauth20_authorize.srf', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post('https://xsts.auth.xboxlive.com/xsts/authorize', [
            RequestOptions::HEADERS => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => [
                'Properties' => [
                    'SandboxId'  => 'RETAIL',
                    'UserTokens' => [
                        $token,
                    ],
                ],
                'RelyingParty' => 'http://xboxlive.com',
                'TokenType'    => 'JWT',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return [
            'token' => $data['Token'],
            'gtg'   => $data['DisplayClaims']['xui'][0]['gtg'],
            'xid'   => $data['DisplayClaims']['xui'][0]['xid'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }
        $response = $this->getAccessTokenResponse($this->getCode());
        $this->user = $this->mapUserToObject($this->getUserByToken($response['token']));
        return $this->user;
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
        $microsoftToken = $this->getMicrosoftToken($code);
        return $this->signInIntoXboxLive($microsoftToken);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(['gtg' => $user['gtg'], 'xid' => $user['xid']])->setToken($user['token']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
            'scope'      => 'User.Read',
        ]);
    }

    /**
     * Get Microsoft Token for sign in.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getMicrosoftToken($code)
    {
        $response = $this->getHttpClient()->post('https://login.live.com/oauth20_token.srf', [
            RequestOptions::FORM_PARAMS => [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $this->redirectUrl,
            ],
            RequestOptions::HEADERS => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        return [
            'token' => $data['access_token'],
        ];
    }

    /**
     * Get a XBOX Live login token.
     *
     * @param array $xboxToken
     *
     * @return array
     */
    protected function signInIntoXboxLive($xboxToken)
    {
        $response = $this->getHttpClient()->post('https://user.auth.xboxlive.com/user/authenticate', [
            RequestOptions::HEADERS => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => [
                'Properties' => [
                    'AuthMethod' => 'RPS',
                    'SiteName'   => 'user.auth.xboxlive.com',
                    'RpsTicket'  => 'd='.$xboxToken['token'],
                ],
                'RelyingParty' => 'http://auth.xboxlive.com',
                'TokenType'    => 'JWT',
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);
        return [
            'token' => $data['Token'],
            'uhs'   => $data['DisplayClaims']['xui'][0]['uhs'],
        ];
    }
}
