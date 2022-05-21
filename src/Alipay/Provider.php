<?php

namespace SocialiteProviders\Alipay;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'alipay';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['auth_user'];

    protected $baseUrl = 'https://openapi.alipay.com/gateway.do';
    protected $authUrl = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm';

    protected $apiVersion = '1.0';
    protected $signType = 'RSA2';
    protected $postCharset = 'UTF-8';
    protected $format = 'json';
    protected $sandbox = false;

    /**
     * @param \SocialiteProviders\Manager\Contracts\OAuth1\ProviderInterface|\SocialiteProviders\Manager\Contracts\OAuth2\ProviderInterface $config
     */
    public function setConfig(ConfigInterface $config)
    {
        parent::setConfig($config);

        $this->sandbox = $this->config['sandbox'] ?? false;
        if ($this->sandbox) {
            $this->baseUrl = 'https://openapi.alipaydev.com/gateway.do';
            $this->authUrl = 'https://openauth.alipaydev.com/oauth2/publicAppAuthorize.htm';
        }

        return $this;
    }

    /**
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return ['sandbox'];
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getAuthUrl()
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->authUrl, $state);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getTokenUrl()
     */
    protected function getTokenUrl()
    {
        return $this->baseUrl;
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getUserByToken()
     */
    protected function getUserByToken($token)
    {
        $params = $this->getPublicFields('alipay.user.info.share');
        $params += ['auth_token' => $token];
        $params['sign'] = $this->generateSign($params);

        $response = $this->getHttpClient()->post(
            $this->baseUrl,
            [
                RequestOptions::FORM_PARAMS => $params,
            ]
        );

        $response = json_decode($response->getBody()->getContents(), true);

        if (!empty($response['error_response']) || empty($response['alipay_user_info_share_response'])) {
            throw new \InvalidArgumentException('Error getting apipay user details '.\json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        return $response['alipay_user_info_share_response'];
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::mapUserToObject()
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['user_id'] ?? null,
            'nickname' => $user['nick_name'] ?? null,
            'name'     => null,
            'email'    => $user['email'] ?? null,
            'avatar'   => $user['avatar'] ?? null,
        ]);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     *
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'app_id'       => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope'        => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getTokenFields()
     */
    protected function getTokenFields($code)
    {
        $params = $this->getPublicFields('alipay.system.oauth.token');
        $params += [
            'code'       => $code,
            'grant_type' => 'authorization_code',
        ];
        $params['sign'] = $this->generateSign($params);

        return $params;
    }

    /**
     * Get the code from the request.
     *
     * @return string
     */
    protected function getCode()
    {
        return $this->request->input('auth_code');
    }

    /**
     * {@inheritdoc}.
     *
     * @see \Laravel\Socialite\Two\AbstractProvider::getAccessToken()
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post(
            $this->getTokenUrl(),
            [
                RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
                RequestOptions::HEADERS     => [
                    'content-type' => 'application/x-www-form-urlencoded;charset=utf-8',
                ],
            ]
        );
        $response = json_decode($response->getBody()->getContents(), true);

        if (!empty($response['error_response'])) {
            throw new \InvalidArgumentException('Error getting apipay access token '.json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        return $response['alipay_system_oauth_token_response'];
    }

    /**
     * Get the public parameters for the request.
     *
     * @param string $method
     *
     * @return array
     */
    public function getPublicFields(string $method)
    {
        return [
            'app_id'    => $this->clientId,
            'format'    => $this->format,
            'charset'   => $this->postCharset,
            'sign_type' => $this->signType,
            'method'    => $method,
            'timestamp' => date('Y-m-d H:m:s'),
            'version'   => $this->apiVersion,
        ];
    }

    /**
     * @see https://opendocs.alipay.com/open/289/105656
     */
    protected function generateSign($params)
    {
        ksort($params);

        return $this->signWithSHA256RSA($this->buildParams($params), $this->clientSecret);
    }

    protected function signWithSHA256RSA(string $signContent, string $key)
    {
        if (empty($key)) {
            throw new \Exception('no RSA private key set.');
        }

        $key = "-----BEGIN RSA PRIVATE KEY-----\n".
            chunk_split($key, 64, "\n").
            '-----END RSA PRIVATE KEY-----';

        openssl_sign($signContent, $signValue, $key, OPENSSL_ALGO_SHA256);

        return base64_encode($signValue);
    }

    public static function buildParams(array $params, bool $urlencode = false, array $except = ['sign'])
    {
        $param_str = '';
        foreach ($params as $k => $v) {
            if (in_array($k, $except)) {
                continue;
            }
            $param_str .= $k.'=';
            $param_str .= $urlencode ? rawurlencode($v) : $v;
            $param_str .= '&';
        }

        return rtrim($param_str, '&');
    }
}
