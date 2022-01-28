<?php

namespace SocialiteProviders\Minecraft;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'MINECRAFT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['XboxLive.signin'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Used to get a token from XBOX Live.
     */
    protected const XBOX_LIVE_TOKEN_URL = 'https://login.live.com/oauth20_token.srf';

    /**
     * Used to sign in into XBOX Live.
     */
    protected const XBOX_LIVE_SIGN_IN_URL = 'https://user.auth.xboxlive.com/user/authenticate';

    /**
     * Used to get a XSTS token from XBOX Live.
     */
    protected const XSTS_TOKEN_URL = 'https://xsts.auth.xboxlive.com/xsts/authorize';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://login.live.com/oauth20_authorize.srf',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.minecraftservices.com/authentication/login_with_xbox';
    }

    /**
     * {@inheritdoc}
     */
    protected function isStateless()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://api.minecraftservices.com/minecraft/profile',
            [RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            ]
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $activeSkin = array_filter($user['skins'], function ($skin) {
            return 'ACTIVE' === $skin['state'];
        });

        return (new MinecraftUser())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => null,
            'name'     => $user['name'],
            'email'    => null,
            'avatar'   => 1 === count($activeSkin) ? $activeSkin[0]['url'] : null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        $xboxToken = $this->getMicrosoftToken($code);
        $loginToken = $this->signInIntoXboxLive($xboxToken);
        $xstsToken = $this->getXstsToken($loginToken);

        return [
            'identityToken'       => sprintf('XBL3.0 x=%s;%s', $loginToken['uhs'], $xstsToken['token']),
            'ensureLegacyEnabled' => true,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get Microsoft Token for sign in.
     *
     * @param $code
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    protected function getMicrosoftToken($code)
    {
        $response = json_decode($this->getHttpClient()->post(
            self::XBOX_LIVE_TOKEN_URL,
            [RequestOptions::HEADERS => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ],
                RequestOptions::FORM_PARAMS => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $this->redirectUrl,
                ],
            ]
        )->getBody(), true);

        return [
            'token' => $response['access_token'],
        ];
    }

    /**
     * Get a XBOX Live login token.
     *
     * @param $xboxToken
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    protected function signInIntoXboxLive($xboxToken)
    {
        $response = json_decode($this->getHttpClient()->post(
            self::XBOX_LIVE_SIGN_IN_URL,
            [RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
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
            ]
        )->getBody(), true);

        return [
            'token' => $response['Token'],
            'uhs'   => $response['DisplayClaims']['xui'][0]['uhs'],
        ];
    }

    /**
     * Get a XSTS token.
     *
     * @param $loginToken
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    protected function getXstsToken($loginToken)
    {
        $response = json_decode($this->getHttpClient()->post(
            self::XSTS_TOKEN_URL,
            [RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
                RequestOptions::JSON => [
                    'Properties' => [
                        'SandboxId'  => 'RETAIL',
                        'UserTokens' => [
                            $loginToken['token'],
                        ],
                    ],
                    'RelyingParty' => 'rp://api.minecraftservices.com/',
                    'TokenType'    => 'JWT',
                ],
            ]
        )->getBody(), true);

        return [
            'token' => $response['Token'],
        ];
    }
}
