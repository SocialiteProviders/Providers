<?php

namespace SocialiteProviders\OpenIDConnect;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
    protected $scopes = self::DEFAULT_SCOPES;

    /**
     * Tracked separately so a configured `scopes` list can replace the
     * defaults without discarding anything added through Socialite's fluent
     * scopes() API.
     */
    protected const DEFAULT_SCOPES = [
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
    protected $usesPKCE = true;

    /**
     * Indicates if id_token signature verification should be enabled.
     * Can be overridden by config 'verify_jwt'.
     *
     * OIDC Core 3.1.3.7 step 6 permits skipping signature validation for an
     * id_token fetched over the back channel, substituting TLS server
     * validation of the token endpoint. That exemption is only as good as the
     * transport, so this defaults to on and getBaseUrl() refuses plaintext
     * issuers; an OP that genuinely cannot serve a JWKS has to opt out.
     */
    protected bool $verifyJwt = true;

    /**
     * Indicates if a missing email should fail the login.
     * Can be overridden by config 'require_email'.
     */
    protected bool $requireEmail = false;

    /**
     * {@inheritdoc}
     */
    public static function additionalConfigKeys(): array
    {
        return [
            'base_url',
            'scopes',
            'use_nonce',
            'require_email',
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
            // Socialite carries the verifier in the session between the
            // redirect and the callback (getCodeChallenge() and
            // getTokenFields() both read it back), so without a session bound
            // this would fail later with an opaque "Session store not set on
            // request." from deep inside the parent.
            if (! $this->request->hasSession()) {
                throw new InvalidArgumentException(
                    'OIDC: PKCE requires a session to carry the code_verifier from the redirect to the callback. '
                    .'Bind a session, or call withoutPKCE() to opt out.'
                );
            }

            $this->request->session()->put('code_verifier', $this->getCodeVerifier());
        }

        return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(): array
    {
        $configured = $this->parseScopeList($this->getConfig('scopes'));

        if ($configured === []) {
            $scopes = $this->scopes;
        } else {
            // Replace the defaults rather than extend them, so an OP that does
            // not support `profile` can be narrowed down. Anything added via
            // Socialite's fluent scopes() is still honoured.
            $scopes = array_merge($configured, array_diff($this->scopes, self::DEFAULT_SCOPES));
        }

        // `openid` is what makes this an OIDC request at all -- without it the
        // OP returns no id_token -- so it is always sent.
        array_unshift($scopes, 'openid');

        // Repeated values are malformed, and some OPs (Azure AD B2C among
        // them) reject the request outright.
        return array_values(array_unique($scopes));
    }

    /**
     * Normalise a scope list given as an array, or as a string separated by
     * whitespace and/or commas.
     *
     * @return string[]
     */
    protected function parseScopeList(mixed $scopes): array
    {
        if ($scopes === null || $scopes === '' || $scopes === []) {
            return [];
        }

        $list = is_array($scopes)
            ? $scopes
            : preg_split('/[\s,]+/', (string) $scopes, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            array_map(static fn ($scope) => is_string($scope) ? trim($scope) : null, $list ?: []),
            static fn (?string $scope) => $scope !== null && $scope !== ''
        ));
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
     *
     * Overridden because the parent hardcodes '?'. Discovered endpoints are
     * not necessarily bare paths -- multi-tenant deployments advertise things
     * like https://idp.example.com/authorize?realm=prod -- and a second '?'
     * folds every parameter after it into the preceding value.
     */
    protected function buildAuthUrlFromBase($url, $state): string
    {
        return $this->appendQuery(
            $url,
            http_build_query($this->getCodeFields($state), '', '&', $this->encodingType)
        );
    }

    /**
     * Append an already-built query string to a URL that may carry one.
     */
    protected function appendQuery(string $url, string $query): string
    {
        if ($query === '') {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').$query;
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
     * Determine if the provider is operating with a nonce.
     *
     * The nonce is minted at the redirect and compared at the callback, which
     * means it has to survive in the session between two requests. A stateless
     * flow has no such session, so requiring one there would fail every login.
     * Dropping it is safe for the authorization code flow used here: the code
     * is bound to this client by PKCE and the exchange happens over the back
     * channel. OIDC Core 3.1.2.1 only makes the nonce REQUIRED for the
     * implicit and hybrid flows, where the token travels via the browser.
     */
    protected function usesNonce(): bool
    {
        if ($this->isStateless()) {
            return false;
        }

        $config = $this->getConfig();

        // Read the raw config for the same reason as shouldVerifyJwt():
        // getConfig() cannot distinguish an explicit false from an absent key.
        if (is_array($config) && isset($config['use_nonce'])) {
            return filter_var($config['use_nonce'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->usesNonce;
    }

    /**
     * Disable the nonce for this request.
     */
    public function withoutNonce(): static
    {
        $this->usesNonce = false;

        return $this;
    }

    /**
     * Disable PKCE for this request.
     *
     * Socialite ships enablePKCE() but no counterpart, and its PKCE support
     * requires a session, so a flow without one needs a way to opt out.
     */
    public function withoutPKCE(): static
    {
        $this->usesPKCE = false;

        return $this;
    }

    /**
     * Get a newly generated nonce.
     */
    protected function getNonce(): string
    {
        return Str::random(40);
    }

    /**
     * Determine if id_token signature verification is enabled.
     *
     * Read straight from the config array rather than via getConfig(), which
     * treats any falsy value as absent and would therefore make an explicit
     * `verify_jwt => false` indistinguishable from "unset" -- leaving no way
     * to opt out now that the default is true.
     */
    protected function shouldVerifyJwt(): bool
    {
        $config = $this->getConfig();

        if (is_array($config) && isset($config['verify_jwt'])) {
            return filter_var($config['verify_jwt'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->verifyJwt;
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
        if ($this->httpClient === null) {
            $this->httpClient = new Client([
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
        return $this->request->hasSession()
            ? $this->request->session()->get('nonce')
            : null;
    }

    /**
     * The issuer base URL, validated to be on a secure transport.
     *
     * Every endpoint this provider talks to is derived from `base_url`, so a
     * plaintext issuer would expose the discovery document, the JWKS and the
     * token exchange to tampering. That matters doubly because OIDC Core
     * 3.1.3.7 step 6 lets an RP substitute TLS server validation for id_token
     * signature checking -- an exemption that is worthless without TLS.
     *
     * Loopback hosts are exempt so local development still works.
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = rtrim((string) $this->getConfig('base_url'), '/');

        if ($baseUrl === '') {
            throw new InvalidArgumentException('OIDC: base_url is not configured.');
        }

        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));

        if ($scheme !== 'https' && ! ($scheme === 'http' && $this->isLoopbackHost($host))) {
            throw new InvalidArgumentException(
                'OIDC: base_url must use https (got '.($scheme ?: 'no scheme').'); plaintext is only permitted for loopback hosts.'
            );
        }

        return $baseUrl;
    }

    /**
     * Loopback hosts, where plaintext HTTP is not remotely interceptable.
     */
    protected function isLoopbackHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
            || str_ends_with($host, '.localhost');
    }

    /**
     * @throws GuzzleException
     */
    protected function getOpenIdConfig(): array
    {
        if ($this->configurations === null) {
            $configUrl = $this->getBaseUrl().'/.well-known/openid-configuration';
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

        if (! is_array($tokenResponse)) {
            throw new InvalidArgumentException('Token endpoint: malformed response.', 401);
        }

        $this->credentialsResponseBody = $tokenResponse;

        // Not every IdP signals failure with a 4xx -- some return 200 with an
        // error body, which Guzzle will not raise for us.
        if (Arr::has($tokenResponse, 'error')) {
            $description = Arr::get($tokenResponse, 'error_description') ?: Arr::get($tokenResponse, 'error');
            throw new InvalidArgumentException('Token endpoint: returned error - '.$description, 401);
        }

        $idToken = Arr::get($tokenResponse, 'id_token');

        if (! is_string($idToken) || $idToken === '') {
            throw new InvalidArgumentException('Token endpoint: response contained no id_token.', 401);
        }

        $accessToken = Arr::get($tokenResponse, 'access_token');

        $claims = (array) $this->decodeJWT($idToken, $accessToken);

        // sub is the only identifier the OP must return (OIDC Core 2), so it
        // is the one claim worth insisting on.
        if (empty($claims['sub'])) {
            throw new InvalidArgumentException('JWT: Missing required sub claim.', 401);
        }

        // The userinfo endpoint is only reachable with an access token; an
        // id_token-only response leaves the id_token claims as all we have.
        if ($this->hasEmptyEmail($claims) && is_string($accessToken) && $accessToken !== '') {
            $claims = $this->mergeUserInfoClaims($claims, $this->getUserByToken($accessToken));
        }

        // email depends on the `email` scope being granted and is not
        // guaranteed, so it is only fatal when explicitly required.
        if ($this->requiresEmail() && $this->hasEmptyEmail($claims)) {
            throw new InvalidArgumentException('JWT: User has no email.', 401);
        }

        $this->user = $this->mapUserToObject($claims);

        // Reimplementing user() means re-establishing what the Manager's base
        // sets, or this provider alone returns null for the standard
        // accessTokenResponseBody and approvedScopes. The former is also where
        // the id_token belongs, rather than being folded into the raw claims.
        if ($this->user instanceof User) {
            $this->user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        // expires_in is optional (RFC 6749 4.2.2), as are access_token and
        // refresh_token here, so read them all defensively -- as Socialite's
        // and the Manager's own user() implementations do.
        return $this->user->setToken($accessToken)
            ->setRefreshToken($this->parseRefreshToken($tokenResponse))
            ->setExpiresIn($this->parseExpiresIn($tokenResponse))
            ->setApprovedScopes($this->parseApprovedScopes($tokenResponse));
    }

    /**
     * Decode an id_token received over the back channel from the token
     * endpoint.
     *
     * Trust model: this token is the response to a request *we* initiated over
     * TLS, and it is bound to this session by the nonce and PKCE verifier.
     * That is what lets signature verification be optional here at all (OIDC
     * Core 3.1.3.7 step 6) -- though it is on by default, since the exemption
     * rests entirely on the transport. Contrast verifyLogoutToken(), which
     * handles an unsolicited token and can never make that trade.
     */
    protected function decodeJWT(string $jwt, ?string $accessToken = null)
    {
        $header = $this->decodeJwtHeader($jwt);
        $alg = $header->alg ?? null;

        if ($this->shouldVerifyJwt()) {
            $payload = $this->verifyAndDecodeJWT($jwt, $alg);
        } else {
            // Unverified: claims are read as-is, so sub/email/groups/role are
            // only as trustworthy as the TLS connection to the token endpoint.
            try {
                [, $jwtPayload] = explode('.', $jwt);
                $payload = json_decode($this->base64UrlDecode($jwtPayload));
            } catch (Exception $e) {
                throw new InvalidArgumentException('JWT: Failed to parse.', 401);
            }
        }

        $this->validateIdTokenClaims($payload, $alg, $accessToken);

        if ($this->usesNonce() && $this->request->hasSession()) {
            $this->request->session()->forget('nonce');
        }

        return $payload;
    }

    /**
     * Verify the JWT signature and decode it.
     *
     * The accepted signing algorithms are pinned by the RP and the token's own
     * `alg` header is only ever checked against that allow list, never used to
     * choose the verification algorithm. Deriving it from the header is the
     * algorithm substitution attack of RFC 8725 section 2.1: php-jwt's only
     * algorithm check compares the header against the Key it was handed, so if
     * both come from the header an attacker can claim `alg: HS256` and
     * HMAC-sign the token with the (non-secret) PEM public key as the MAC
     * secret. OIDC Core 3.1.3.7 step 7 requires the registered
     * `id_token_signed_response_alg` instead.
     */
    protected function verifyAndDecodeJWT(string $jwt, ?string $alg)
    {
        $allowed = $this->getAllowedSigningAlgorithms();

        if ($alg === null || ! in_array($alg, $allowed, true)) {
            throw new InvalidArgumentException(
                'JWT: Disallowed signing algorithm ['.($alg ?? 'missing').']; expected one of ['.implode(', ', $allowed).'].',
                401
            );
        }

        try {
            JWT::$leeway = (int) ($this->getConfig('clock_skew') ?? 0);

            $publicKey = $this->getConfig('jwt_public_key');

            if ($publicKey) {
                // A PEM public key or certificate is not secret, so it must
                // never be usable as an HMAC secret.
                if ($this->isHmacAlgorithm($alg) && $this->isAsymmetricKey($publicKey)) {
                    throw new InvalidArgumentException('HMAC algorithms cannot be used with an asymmetric public key.');
                }

                $decoded = JWT::decode($jwt, new Key($publicKey, $alg));
            } else {
                // A JWKS distributes asymmetric public keys; an HMAC secret is
                // never published this way.
                if ($this->isHmacAlgorithm($alg)) {
                    throw new InvalidArgumentException('HMAC algorithms cannot be verified against a JWKS.');
                }

                $kid = $this->decodeJwtHeader($jwt)->kid ?? null;
                $jwks = $this->getJwks();

                if ($kid && ! $this->jwksContainsKid($jwks, $kid)) {
                    Cache::forget($this->jwksCacheKey());
                    $jwks = $this->getJwks();
                }

                $decoded = JWT::decode($jwt, JWK::parseKeySet($jwks, $alg));
            }

            return json_decode(json_encode($decoded));
        } catch (Exception $e) {
            throw new InvalidArgumentException('JWT: Verification failed - '.$e->getMessage(), 401);
        }
    }

    /**
     * The signing algorithms this client accepts, in order of preference:
     * the pinned `jwt_algorithm` config, else the OP's advertised
     * `id_token_signing_alg_values_supported`, else RS256.
     *
     * `none` is never accepted.
     *
     * @return string[]
     *
     * @throws GuzzleException
     */
    protected function getAllowedSigningAlgorithms(): array
    {
        $configured = $this->getConfig('jwt_algorithm');

        if ($configured) {
            $algorithms = is_array($configured)
                ? $configured
                : preg_split('/[\s,]+/', (string) $configured, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $algorithms = (array) ($this->getOpenIdConfig()['id_token_signing_alg_values_supported'] ?? []);
        }

        $algorithms = array_values(array_filter(
            array_map(static fn ($alg) => is_string($alg) ? trim($alg) : null, $algorithms),
            static fn (?string $alg) => $alg !== null && $alg !== '' && strcasecmp($alg, 'none') !== 0
        ));

        return $algorithms ?: ['RS256'];
    }

    /**
     * Determine if the algorithm is a symmetric (HMAC) one.
     */
    protected function isHmacAlgorithm(string $alg): bool
    {
        return str_starts_with(strtoupper($alg), 'HS');
    }

    /**
     * Determine if the key material is an asymmetric public key or certificate,
     * i.e. public information that must not double as an HMAC secret.
     */
    protected function isAsymmetricKey(mixed $key): bool
    {
        if ($key instanceof \OpenSSLAsymmetricKey || $key instanceof \OpenSSLCertificate) {
            return true;
        }

        return is_string($key) && str_contains($key, '-----BEGIN');
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
        return 'openidconnect_jwks_'.md5($this->getBaseUrl());
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
     *
     * exp is required, not optional: OIDC Core 3.1.3.7 step 9 requires the
     * current time to be before it. php-jwt is no backstop here because its
     * own decode() gates the exp check on isset() too (JWT.php), so a token
     * that simply omits the claim would be accepted forever by both paths.
     *
     * @param  bool  $requireIat  Back-Channel Logout 2.4 requires iat as well.
     */
    protected function validateTimeClaims($payload, bool $requireIat = false): void
    {
        $now = time();
        $leeway = (int) ($this->getConfig('clock_skew') ?? 0);

        if (! isset($payload->exp) || ! is_numeric($payload->exp)) {
            throw new InvalidArgumentException('JWT: Missing required exp claim.', 401);
        }

        if ($now - $leeway >= (int) $payload->exp) {
            throw new InvalidArgumentException('JWT: Token has expired.', 401);
        }

        if ($requireIat && (! isset($payload->iat) || ! is_numeric($payload->iat))) {
            throw new InvalidArgumentException('JWT: Missing required iat claim.', 401);
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

    /**
     * Merge the UserInfo response over the id_token claims.
     *
     * OIDC Core 5.3.2 requires the sub of the UserInfo response to be verified
     * against the sub of the id_token, and the response not to be used if they
     * do not match exactly.
     *
     * Merged rather than substituted so claims the id_token carried but
     * UserInfo does not -- groups and role, typically -- are not lost.
     */
    protected function mergeUserInfoClaims(array $claims, $userInfo): array
    {
        if (! is_array($userInfo)) {
            return $claims;
        }

        if (($userInfo['sub'] ?? null) !== $claims['sub']) {
            throw new InvalidArgumentException('UserInfo: sub does not match the id_token.', 401);
        }

        return array_merge($claims, array_filter($userInfo, static fn ($value) => $value !== null));
    }

    /**
     * Determine if a missing email should fail the login.
     *
     * Off by default: email is not a guaranteed claim, since it depends on the
     * `email` scope being granted, and OIDC Core 2 makes sub the only
     * identifier an OP must return.
     */
    protected function requiresEmail(): bool
    {
        $config = $this->getConfig();

        if (is_array($config) && isset($config['require_email'])) {
            return filter_var($config['require_email'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->requireEmail;
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
            // Built by hand rather than via Guzzle's `auth` option, which
            // base64s the raw "id:secret" pair. RFC 6749 2.3.1 requires each
            // half to be form-urlencoded first, and the two only agree for
            // alphanumeric credentials. A secret containing +, /, =, a space
            // or non-ASCII would otherwise come back as an opaque
            // invalid_client -- and base64-derived secrets routinely contain
            // + and /.
            $options[RequestOptions::HEADERS]['Authorization'] = 'Basic '.base64_encode(
                urlencode($this->clientId).':'.urlencode($this->clientSecret)
            );

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
     * @param  string|null  $idToken  id_token returned at login; most IdPs require it as `id_token_hint`.
     * @param  string|null  $postLogoutRedirectUri  Optional override; falls back to the `post_logout_redirect_uri` config.
     * @param  array  $extra  Additional query params (e.g. `ui_locales`).
     *
     * @throws GuzzleException
     */
    public function logout(?string $idToken = null, ?string $postLogoutRedirectUri = null, array $extra = []): RedirectResponse
    {
        $config = $this->getOpenIdConfig();

        if (empty($config['end_session_endpoint'])) {
            throw new InvalidArgumentException('Provider does not advertise an end_session_endpoint.');
        }

        // Only sent when it can actually be stored, and therefore checked
        // later by validateLogoutState(). Emitting a state with nowhere to
        // remember it would look like protection without being any.
        $state = null;

        if ($this->request->hasSession()) {
            $this->request->session()->put('logout_state', $state = Str::random(40));
        }

        $params = array_filter(array_merge([
            'id_token_hint'            => $idToken,
            'client_id'                => $this->clientId,
            'post_logout_redirect_uri' => $postLogoutRedirectUri ?? $this->getConfig('post_logout_redirect_uri'),
            'state'                    => $state,
        ], $extra), fn ($v) => $v !== null && $v !== '');

        return new RedirectResponse($this->appendQuery($config['end_session_endpoint'], http_build_query($params)));
    }

    /**
     * Validate the `state` the IdP returns to the post_logout_redirect_uri.
     *
     * logout() mints a state and stores it in the session; this is the other
     * half, and without calling it the state is decorative. Consume it in the
     * controller backing your post_logout_redirect_uri before acting on the
     * redirect.
     *
     * The stored value is pulled, so a state is good for exactly one
     * round trip and a replayed redirect fails.
     *
     * @param  Request|null  $request  Defaults to the provider's request.
     */
    public function validateLogoutState(?Request $request = null): bool
    {
        $request ??= $this->request;

        if (! $request->hasSession()) {
            return false;
        }

        $expected = $request->session()->pull('logout_state');
        $returned = $request->input('state');

        return is_string($expected)
            && $expected !== ''
            && is_string($returned)
            && hash_equals($expected, $returned);
    }

    /**
     * Revoke an access or refresh token at the IdP's revocation endpoint.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc7009
     *
     * @param  string  $token  The token to revoke.
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

        $options = $this->tokenRequestOptions([
            'token'           => $token,
            'token_type_hint' => $tokenTypeHint,
            'client_id'       => $this->clientId,
            'client_secret'   => $this->clientSecret,
        ]);

        // Guzzle raises on 4xx/5xx by default, which would leave the return
        // value below unreachable -- only a 2xx could ever get there, so the
        // result was always true. Worse, a non-conforming IdP answering 400
        // for an already-revoked token would throw a ClientException out of
        // the caller's logout flow. Branch on the status instead.
        $options[RequestOptions::HTTP_ERRORS] = false;

        $response = $this->getHttpClient()->post($config['revocation_endpoint'], $options);

        // RFC 7009 section 2.2: a conforming server answers 200 whether or not
        // the token was still valid. Some answer 204. Anything else means the
        // revocation did not happen -- a 400 for an already-revoked token, or
        // a 401 for rejected client credentials.
        return in_array($response->getStatusCode(), [200, 204], true);
    }

    /**
     * Verify a back-channel logout token posted to the RP by the IdP.
     *
     * @see https://openid.net/specs/openid-connect-backchannel-1_0.html
     *
     * Trust model: unlike decodeJWT(), this token arrives unsolicited from the
     * public internet on an unauthenticated endpoint. There is no session
     * nonce, no PKCE verifier and no request of ours for it to answer, so the
     * TLS-in-place-of-signature exemption of OIDC Core 3.1.3.7 step 6 does not
     * apply and cannot be opted out of: the signature is the only control.
     * Verification is therefore always performed here regardless of
     * `verify_jwt`, followed by the spec's claim rules. Returns the decoded
     * payload so the caller can destroy sessions matching `sid` / `sub`.
     *
     * The caller is responsible for:
     *   - ensuring the jti has not been seen before (replay protection),
     *   - mapping `sid`/`sub` to local sessions and invalidating them.
     *
     * @throws InvalidArgumentException if the token is invalid.
     */
    public function verifyLogoutToken(string $logoutToken): array
    {
        $header = $this->decodeJwtHeader($logoutToken);
        $alg = $header->alg ?? null;

        $payload = $this->verifyAndDecodeJWT($logoutToken, $alg);

        $expectedIssuer = $this->getConfig('issuer') ?: ($this->getOpenIdConfig()['issuer'] ?? null);
        if ($expectedIssuer !== null && ($payload->iss ?? null) !== $expectedIssuer) {
            throw new InvalidArgumentException('Logout token: invalid issuer.', 401);
        }

        $aud = $payload->aud ?? null;
        $audList = is_array($aud) ? $aud : [$aud];
        if (! in_array($this->clientId, $audList, true)) {
            throw new InvalidArgumentException('Logout token: invalid audience.', 401);
        }

        $this->validateTimeClaims($payload, requireIat: true);

        if (empty($payload->jti ?? null)) {
            throw new InvalidArgumentException('Logout token: missing jti.', 401);
        }

        if (isset($payload->nonce)) {
            throw new InvalidArgumentException('Logout token: must not contain a nonce.', 401);
        }

        $events = (array) ($payload->events ?? []);
        if (! array_key_exists('http://schemas.openid.net/event/backchannel-logout', $events)) {
            throw new InvalidArgumentException('Logout token: missing backchannel-logout event.', 401);
        }

        if (empty($payload->sub ?? null) && empty($payload->sid ?? null)) {
            throw new InvalidArgumentException('Logout token: must contain sub and/or sid.', 401);
        }

        return (array) $payload;
    }
}
