<?php

namespace SocialiteProviders\OpenIDConnect;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OPENIDCONNECT';

    public $configurations = null;

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'email',
        'profile',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * Indicates if the nonce should be utilized.
     */
    protected bool $usesNonce = true;

    /**
     * Use PKCE by default (Authorization Code Flow + PKCE).
     * This follows OAuth 2.1 / current OIDC best practice.
     */
    protected $pkceEnabled = true;

    /**
     * Indicates if JWT signature verification should be enabled.
     * Can be overridden by config 'verify_jwt'.
     */
    protected bool $verifyJwt = false;

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'base_url',
            'scopes',
            'verify_jwt',
            'jwt_public_key',
            'jwt_algorithm',
            'issuer',
            'token_auth_method',
            'post_logout_redirect_uri',
            'cache_ttl',
            'clock_skew',
            'http_timeout',
            'http_connect_timeout',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function redirect(): RedirectResponse
    {
        $state = null;

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
        }

        if ($this->usesNonce()) {
            $this->request->session()->put('nonce', $this->getNonce());
        }

        if ($this->usesPKCE()) {
            $this->request->session()->put('code_verifier', $this->getCodeVerifier());
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(): array
    {
        if ($this->getConfig('scopes')) {
            return array_merge($this->scopes, explode(' ', $this->getConfig('scopes')));
        }

        return $this->scopes;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): string
    {
        return $this->getOpenIdConfig()['token_endpoint'];
    }

    /**
     * Get the userinfo URL for the provider.
     *
     * @throws GuzzleException
     */
    protected function getUserInfoUrl(): string
    {
        return $this->getOpenIdConfig()['userinfo_endpoint'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            $this->getOpenIdConfig()['authorization_endpoint'],
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);

        if ($this->usesNonce()) {
            $fields['nonce'] = $this->getCurrentNonce();
        }

        return $fields;
    }

    /**
     * Determine if the provider is operating with nonce.
     */
    protected function usesNonce(): bool
    {
        return $this->usesNonce;
    }

    /**
     * Get a newly generated nonce.
     */
    protected function getNonce(): string
    {
        return Str::random(40);
    }

    /**
     * Determine if JWT signature verification is enabled.
     */
    protected function shouldVerifyJwt(): bool
    {
        return (bool) $this->getConfig('verify_jwt', $this->verifyJwt);
    }

    /**
     * TTL (in seconds) for the cached discovery document and JWKS.
     */
    protected function getCacheTtl(): int
    {
        return (int) ($this->getConfig('cache_ttl') ?: 3600);
    }

    /**
     * {@inheritdoc}
     *
     * Apply connect/read timeouts so a slow or hanging IdP doesn't tie up
     * PHP workers. Defaults: 5s connect, 10s total.
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new \GuzzleHttp\Client([
                'connect_timeout' => (float) ($this->getConfig('http_connect_timeout') ?: 5),
                'timeout'         => (float) ($this->getConfig('http_timeout') ?: 10),
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Get the current nonce stored in the session.
     */
    protected function getCurrentNonce(): ?string
    {
        return $this->request->session()->get('nonce');
    }

    /**
     * @throws GuzzleException
     */
    protected function getOpenIdConfig(): array
    {
        if ($this->configurations === null) {
            $configUrl = rtrim($this->getConfig('base_url'), '/').'/.well-known/openid-configuration';
            $cacheKey = 'openidconnect_discovery_'.md5($configUrl);

            $this->configurations = Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($configUrl) {
                try {
                    $response = $this->getHttpClient()->get($configUrl);

                    return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                } catch (Exception $e) {
                    throw new InvalidArgumentException('Unable to get the OIDC configuration from '.$configUrl.': '.$e->getMessage());
                }
            });
        }

        return $this->configurations;
    }

    /**
     * Get the JSON Web Key Set from the OIDC provider.
     *
     * @throws GuzzleException
     */
    protected function getJwks(): array
    {
        return Cache::remember($this->jwksCacheKey(), $this->getCacheTtl(), function () {
            $config = $this->getOpenIdConfig();

            if (! isset($config['jwks_uri'])) {
                throw new InvalidArgumentException('JWKS URI not found in OIDC configuration');
            }

            try {
                $response = $this->getHttpClient()->get($config['jwks_uri']);

                return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Unable to fetch JWKS: '.$e->getMessage());
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->request->filled('error')) {
            $description = $this->request->input('error_description') ?: $this->request->input('error');
            throw new InvalidArgumentException('Callback: IdP returned error - '.$description, 401);
        }

        if ($this->hasInvalidState()) {
            throw new InvalidArgumentException('Callback: invalid state.', 401);
        }

        if (! $this->request->filled('code')) {
            throw new InvalidArgumentException('Callback: missing authorization code.', 401);
        }

        $tokenResponse = $this->getAccessTokenResponse($this->request->input('code'));

        $payload = $this->decodeJWT($tokenResponse['id_token'], $tokenResponse['access_token'] ?? null);

        if ($this->hasEmptyEmail($payload)) {
            $payload = $this->getUserByToken($tokenResponse['access_token']);
            if (empty($payload['email'] ?? null)) {
                throw new InvalidArgumentException('JWT: User has no email.', 401);
            }
        }

        $raw = (array) $payload;
        $raw['id_token'] = $tokenResponse['id_token'];

        $this->user = $this->mapUserToObject($raw);

        return $this->user->setToken($tokenResponse['access_token'])
            ->setRefreshToken($tokenResponse['refresh_token'] ?? null)
            ->setExpiresIn($tokenResponse['expires_in']);
    }

    protected function decodeJWT(string $jwt, ?string $accessToken = null)
    {
        $header = $this->decodeJwtHeader($jwt);
        $alg = $header->alg ?? null;

        if ($this->shouldVerifyJwt()) {
            $payload = $this->verifyAndDecodeJWT($jwt, $alg);
        } else {
            try {
                [, $jwtPayload] = explode('.', $jwt);
                $payload = json_decode($this->base64UrlDecode($jwtPayload));
            } catch (Exception $e) {
                throw new InvalidArgumentException('JWT: Failed to parse.', 401);
            }
        }

        $this->validateIdTokenClaims($payload, $alg, $accessToken);

        if ($this->usesNonce()) {
            $this->request->session()->forget('nonce');
        }

        return $payload;
    }

    /**
     * Verify the JWT signature and decode it.
     */
    protected function verifyAndDecodeJWT(string $jwt, ?string $alg)
    {
        try {
            JWT::$leeway = (int) ($this->getConfig('clock_skew') ?? 0);

            $publicKey = $this->getConfig('jwt_public_key');
            $configuredAlg = $this->getConfig('jwt_algorithm') ?: $alg ?: 'RS256';

            if ($publicKey) {
                $decoded = JWT::decode($jwt, new Key($publicKey, $configuredAlg));
            } else {
                $kid = $this->decodeJwtHeader($jwt)->kid ?? null;
                $jwks = $this->getJwks();

                if ($kid && ! $this->jwksContainsKid($jwks, $kid)) {
                    Cache::forget($this->jwksCacheKey());
                    $jwks = $this->getJwks();
                }

                $decoded = JWT::decode($jwt, JWK::parseKeySet($jwks, $configuredAlg));
            }

            return json_decode(json_encode($decoded));
        } catch (Exception $e) {
            throw new InvalidArgumentException('JWT: Verification failed - '.$e->getMessage(), 401);
        }
    }

    protected function jwksContainsKid(array $jwks, string $kid): bool
    {
        foreach ($jwks['keys'] ?? [] as $key) {
            if (($key['kid'] ?? null) === $kid) {
                return true;
            }
        }

        return false;
    }

    protected function jwksCacheKey(): string
    {
        return 'openidconnect_jwks_'.md5($this->getConfig('base_url'));
    }

    protected function decodeJwtHeader(string $jwt)
    {
        try {
            [$headerB64] = explode('.', $jwt);

            return json_decode($this->base64UrlDecode($headerB64));
        } catch (Exception $e) {
            throw new InvalidArgumentException('JWT: Failed to parse header.', 401);
        }
    }

    /**
     * Validate the standard OIDC id_token claims: nonce, iss, aud, azp, at_hash.
     */
    protected function validateIdTokenClaims($payload, ?string $alg, ?string $accessToken): void
    {
        if ($this->isInvalidNonce($payload->nonce ?? null)) {
            throw new InvalidArgumentException('JWT: Contains an invalid nonce.', 401);
        }

        $expectedIssuer = $this->getConfig('issuer') ?: ($this->getOpenIdConfig()['issuer'] ?? null);
        if ($expectedIssuer !== null && ($payload->iss ?? null) !== $expectedIssuer) {
            throw new InvalidArgumentException('JWT: Invalid issuer.', 401);
        }

        $aud = $payload->aud ?? null;
        $audList = is_array($aud) ? $aud : [$aud];
        if (! in_array($this->clientId, $audList, true)) {
            throw new InvalidArgumentException('JWT: Invalid audience.', 401);
        }

        if (is_array($aud) && count($aud) > 1 && ($payload->azp ?? null) !== $this->clientId) {
            throw new InvalidArgumentException('JWT: Invalid authorized party (azp).', 401);
        }

        if ($accessToken !== null && isset($payload->at_hash) && $alg) {
            $this->validateAtHash($payload->at_hash, $accessToken, $alg);
        }

        $this->validateTimeClaims($payload);
    }

    /**
     * Validate exp/nbf/iat with configurable leeway. Runs in both verified
     * and unverified paths so a stale id_token is never accepted even when
     * signature verification is disabled.
     */
    protected function validateTimeClaims($payload): void
    {
        $now = time();
        $leeway = (int) ($this->getConfig('clock_skew') ?? 0);

        if (isset($payload->exp) && $now - $leeway >= (int) $payload->exp) {
            throw new InvalidArgumentException('JWT: Token has expired.', 401);
        }

        if (isset($payload->nbf) && $now + $leeway < (int) $payload->nbf) {
            throw new InvalidArgumentException('JWT: Token not yet valid.', 401);
        }

        if (isset($payload->iat) && $now + $leeway < (int) $payload->iat) {
            throw new InvalidArgumentException('JWT: Token issued in the future.', 401);
        }
    }

    protected function validateAtHash(string $atHash, string $accessToken, string $alg): void
    {
        $map = ['256' => 'sha256', '384' => 'sha384', '512' => 'sha512'];
        $bits = substr($alg, -3);

        if (! isset($map[$bits])) {
            return;
        }

        $digest = hash($map[$bits], $accessToken, true);
        $expected = $this->base64UrlEncode(substr($digest, 0, intdiv(strlen($digest), 2)));

        if (! hash_equals($expected, $atHash)) {
            throw new InvalidArgumentException('JWT: at_hash mismatch.', 401);
        }
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Determine if the current token has a mismatching nonce.
     */
    protected function isInvalidNonce($nonce): bool
    {
        if (! $this->usesNonce()) {
            return false;
        }

        return ! (is_string($nonce) && strlen($nonce) > 0 && $nonce === $this->getCurrentNonce());
    }

    protected function hasEmptyEmail($payload): bool
    {
        if (is_array($payload)) {
            return empty($payload['email'] ?? null);
        }

        return empty($payload->email ?? null);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'          => $user['sub'] ?? null,
            'email'       => $user['email'] ?? null,
            'name'        => $user['name'] ?? null,
            'nickname'    => $user['nickname'] ?? null,
            'given_name'  => $user['given_name'] ?? null,
            'family_name' => $user['family_name'] ?? null,
            'idp'         => $user['idp'] ?? null,
            'role'        => $user['role'] ?? null,
            'groups'      => $user['groups'] ?? null,
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws JsonException|GuzzleException
     */
    public function getAccessTokenResponse($code)
    {
        $fields = array_merge(
            $this->getTokenFields($code),
            ['grant_type' => 'authorization_code']
        );

        $response = $this->getHttpClient()->post($this->getTokenUrl(), $this->tokenRequestOptions($fields));

        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Build Guzzle request options for the token endpoint, applying the
     * configured client authentication method (client_secret_post or
     * client_secret_basic).
     */
    protected function tokenRequestOptions(array $fields): array
    {
        $method = $this->resolveTokenAuthMethod();

        $options = [RequestOptions::HEADERS => ['Accept' => 'application/json']];

        if ($method === 'client_secret_basic') {
            $options[RequestOptions::AUTH] = [$this->clientId, $this->clientSecret];
            unset($fields['client_id'], $fields['client_secret']);
        }

        $options[RequestOptions::FORM_PARAMS] = $fields;

        return $options;
    }

    /**
     * Pick the client authentication method. Explicit config wins; otherwise
     * we consult `token_endpoint_auth_methods_supported` from discovery and
     * prefer client_secret_basic (the OIDC-registered default) when offered.
     */
    protected function resolveTokenAuthMethod(): string
    {
        $configured = $this->getConfig('token_auth_method');
        if ($configured) {
            return $configured;
        }

        $supported = $this->getOpenIdConfig()['token_endpoint_auth_methods_supported'] ?? [];
        if (in_array('client_secret_basic', $supported, true)) {
            return 'client_secret_basic';
        }

        return 'client_secret_post';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getUserInfoUrl(), [
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
    public function refreshToken($refreshToken)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), $this->tokenRequestOptions([
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]));

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Build an RP-initiated logout redirect.
     *
     * @see https://openid.net/specs/openid-connect-rpinitiated-1_0.html
     *
     * @param  string|null  $idToken                 id_token returned at login; most IdPs require it as `id_token_hint`.
     * @param  string|null  $postLogoutRedirectUri  Optional override; falls back to the `post_logout_redirect_uri` config.
     * @param  array        $extra                   Additional query params (e.g. `ui_locales`).
     *
     * @throws GuzzleException
     */
    public function logout(?string $idToken = null, ?string $postLogoutRedirectUri = null, array $extra = []): RedirectResponse
    {
        $config = $this->getOpenIdConfig();

        if (empty($config['end_session_endpoint'])) {
            throw new InvalidArgumentException('Provider does not advertise an end_session_endpoint.');
        }

        $state = Str::random(40);
        if ($this->request->hasSession()) {
            $this->request->session()->put('logout_state', $state);
        }

        $params = array_filter(array_merge([
            'id_token_hint'            => $idToken,
            'client_id'                => $this->clientId,
            'post_logout_redirect_uri' => $postLogoutRedirectUri ?? $this->getConfig('post_logout_redirect_uri'),
            'state'                    => $state,
        ], $extra), fn ($v) => $v !== null && $v !== '');

        return new RedirectResponse($config['end_session_endpoint'].'?'.http_build_query($params));
    }

    /**
     * Revoke an access or refresh token at the IdP's revocation endpoint.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc7009
     *
     * @param  string  $token          The token to revoke.
     * @param  string  $tokenTypeHint  'access_token' or 'refresh_token'.
     *
     * @throws GuzzleException
     */
    public function revoke(string $token, string $tokenTypeHint = 'refresh_token'): bool
    {
        $config = $this->getOpenIdConfig();

        if (empty($config['revocation_endpoint'])) {
            throw new InvalidArgumentException('Provider does not advertise a revocation_endpoint.');
        }

        $response = $this->getHttpClient()->post(
            $config['revocation_endpoint'],
            $this->tokenRequestOptions([
                'token'           => $token,
                'token_type_hint' => $tokenTypeHint,
                'client_id'       => $this->clientId,
                'client_secret'   => $this->clientSecret,
            ])
        );

        // RFC 7009: a successful response is 200, regardless of whether the
        // token was valid. Some IdPs return 204.
        return in_array($response->getStatusCode(), [200, 204], true);
    }
}
