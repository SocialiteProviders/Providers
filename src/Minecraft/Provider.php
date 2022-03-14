<?php

namespace SocialiteProviders\Minecraft;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'MINECRAFT';

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
    protected $scopes = ['XboxLive.signin'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

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
        $response = $this->getHttpClient()->get('https://api.minecraftservices.com/minecraft/profile', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
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
        $activeSkin = array_filter($user['skins'], function ($skin) {
            return 'ACTIVE' === $skin['state'];
        });

        $uuid = preg_replace('/(.{8})(.{4})(.{4})(.{4})(.{12})/', '$1-$2-$3-$4-$5', $user['id']);

        $avatar = count($activeSkin) === 1 ? $activeSkin[0]['url'] : null;

        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'uuid'     => $uuid,
            'nickname' => null,
            'name'     => $user['name'],
            'email'    => null,
            'avatar'   => $avatar,
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
            'ensureLegacyEnabled' => true,
            'identityToken'       => sprintf('XBL3.0 x=%s;%s', $loginToken['uhs'], $xstsToken['token']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => $this->getTokenFields($code),
        ]);

        return json_decode((string) $response->getBody(), true);
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
        $response = $this->getHttpClient()->post(self::XBOX_LIVE_TOKEN_URL, [
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
        $response = $this->getHttpClient()->post(self::XBOX_LIVE_SIGN_IN_URL, [
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

    /**
     * Get a XSTS token.
     *
     * @param array $loginToken
     *
     * @return array
     */
    protected function getXstsToken($loginToken)
    {
        $response = $this->getHttpClient()->post(self::XSTS_TOKEN_URL, [
            RequestOptions::HEADERS => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
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
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return [
            'token' => $data['Token'],
        ];
    }
}
