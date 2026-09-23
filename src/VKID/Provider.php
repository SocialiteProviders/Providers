<?php

namespace SocialiteProviders\VKID;

use GuzzleHttp\RequestOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\InvalidStateException;
use RuntimeException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'VKID';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email'];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Cached OAuth callback payload (supports both query params and `payload` JSON).
     *
     * @var array{code?: ?string, state?: ?string, device_id?: ?string, type?: ?string}|null
     */
    private $callbackPayload;

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys()
    {
        return [
            'confidential',
            'pkce_ttl',
            'cache_store',
            'cache_prefix',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Always send `state` (required by VK ID, min 32 chars) even when using
     * `stateless()`, and store PKCE verifier in cache instead of the session.
     */
    public function redirect()
    {
        $state = $this->parameters['state'] ?? $this->getState();
        $this->parameters['state'] = $state;

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://id.vk.ru/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://id.vk.ru/oauth2/auth';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $state = (string) ($this->parameters['state'] ?? $state ?? $this->getState());
        $this->parameters['state'] = $state;

        $verifier = $this->generateCodeVerifier();
        $this->cache()->put(
            $this->pkceCacheKey($state),
            $verifier,
            now()->addMinutes((int) $this->getConfig('pkce_ttl', 10))
        );

        $fields = [
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUrl,
            'scope'                 => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type'         => 'code',
            'state'                 => $state,
            'code_challenge'        => $this->codeChallengeS256($verifier),
            'code_challenge_method' => 'S256',
        ];

        $custom = Arr::except($this->parameters, [
            'client_id',
            'redirect_uri',
            'scope',
            'response_type',
            'state',
            'code_challenge',
            'code_challenge_method',
        ]);

        return array_merge($fields, $custom);
    }

    /**
     * {@inheritdoc}
     *
     * VK ID rejects unknown/incorrect token parameters with HTTP 404.
     * Public apps must not send classic OAuth `client_secret`; confidential
     * apps send `service_token` instead (enable via config `confidential`).
     */
    protected function getTokenFields($code)
    {
        $payload = $this->callbackPayload();
        $state = (string) ($payload['state'] ?? '');
        $deviceId = (string) ($payload['device_id'] ?? '');

        if ($state === '') {
            throw new InvalidStateException('VK ID: missing state in callback.');
        }

        if ($deviceId === '') {
            throw new InvalidStateException('VK ID: missing device_id in callback.');
        }

        $verifier = $this->cache()->pull($this->pkceCacheKey($state));

        if (! is_string($verifier) || $verifier === '') {
            throw new InvalidStateException('VK ID: authorization timed out. Please try again.');
        }

        $fields = [
            'grant_type'    => 'authorization_code',
            'code'          => (string) $code,
            'code_verifier' => $verifier,
            'redirect_uri'  => $this->redirectUrl,
            'client_id'     => $this->clientId,
            'device_id'     => $deviceId,
            'state'         => $state,
        ];

        if ($this->shouldSendServiceToken()) {
            $fields['service_token'] = $this->clientSecret;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCode()
    {
        $code = $this->callbackPayload()['code'] ?? null;

        if (! is_string($code) || $code === '') {
            throw new RuntimeException('VK ID: missing authorization code.');
        }

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post('https://id.vk.ru/oauth2/user_info', [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => [
                'access_token' => is_array($token) ? $token['access_token'] : $token,
                'client_id'    => $this->clientId,
            ],
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::TIMEOUT => 15,
        ]);

        $contents = (string) $response->getBody();
        $response = json_decode($contents, true);

        if (! is_array($response) || ! isset($response['user'])) {
            throw new RuntimeException(sprintf(
                'Invalid JSON response from VK: %s',
                $contents
            ));
        }

        return $response['user'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => (string) Arr::get($user, 'user_id'),
            'nickname' => null,
            'name'     => trim(Arr::get($user, 'first_name').' '.Arr::get($user, 'last_name')) ?: null,
            'email'    => Arr::get($user, 'email'),
            'avatar'   => Arr::get($user, 'avatar'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getState()
    {
        return Str::random(40);
    }

    /**
     * @return array{code?: ?string, state?: ?string, device_id?: ?string, type?: ?string}
     */
    protected function callbackPayload()
    {
        if ($this->callbackPayload !== null) {
            return $this->callbackPayload;
        }

        $rawPayload = $this->request->input('payload');

        if (is_string($rawPayload) && $rawPayload !== '') {
            $decoded = json_decode($rawPayload, true);

            if (is_array($decoded)) {
                return $this->callbackPayload = [
                    'code'      => isset($decoded['code']) ? (string) $decoded['code'] : null,
                    'state'     => isset($decoded['state']) ? (string) $decoded['state'] : null,
                    'device_id' => isset($decoded['device_id']) ? (string) $decoded['device_id'] : null,
                    'type'      => isset($decoded['type']) ? (string) $decoded['type'] : null,
                ];
            }
        }

        return $this->callbackPayload = [
            'code'      => $this->request->input('code') !== null ? (string) $this->request->input('code') : null,
            'state'     => $this->request->input('state') !== null ? (string) $this->request->input('state') : null,
            'device_id' => $this->request->input('device_id') !== null ? (string) $this->request->input('device_id') : null,
        ];
    }

    /**
     * @return string
     */
    protected function pkceCacheKey($state)
    {
        $prefix = (string) $this->getConfig('cache_prefix', 'socialite:vkid:pkce:');

        return $prefix.$state;
    }

    /**
     * @return bool
     */
    protected function shouldSendServiceToken()
    {
        if (! is_string($this->clientSecret) || $this->clientSecret === '') {
            return false;
        }

        return (bool) $this->getConfig('confidential', false);
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cache()
    {
        $store = $this->getConfig('cache_store');

        if (is_string($store) && $store !== '') {
            return Cache::store($store);
        }

        return Cache::store();
    }

    /**
     * @param  int  $length
     * @return string
     */
    protected function generateCodeVerifier($length = 64)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $verifier = '';

        for ($i = 0; $i < $length; $i++) {
            $verifier .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $verifier;
    }

    /**
     * @param  string  $verifier
     * @return string
     */
    protected function codeChallengeS256($verifier)
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
