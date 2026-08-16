<?php

namespace SocialiteProviders\OpenIDConnect;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Container\Container;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Manager\Contracts\ConfigInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\OpenIDConnect\IssuerValidators\DefaultIssuerValidator;
use SocialiteProviders\OpenIDConnect\IssuerValidators\IssuerValidator;
use stdClass;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'OPENIDCONNECT';

    protected const DEFAULT_SCOPES = ['openid', 'email', 'profile'];

    protected const BACKCHANNEL_LOGOUT_EVENT = 'http://schemas.openid.net/event/backchannel-logout';

    public const NONCE_SESSION_KEY = 'openidconnect_nonce';

    public $configurations = null;

    protected $scopes = self::DEFAULT_SCOPES;

    protected $scopeSeparator = ' ';

    protected bool $usesNonce = true;

    protected $usesPKCE = true;

    protected bool $verifyJwt = true;

    protected bool $requireEmail = false;

    /** @var IssuerValidator|callable|null */
    protected $issuerValidator = null;

    public static function additionalConfigKeys(): array
    {
        return [
            'base_url',
            'scopes',
            'use_nonce',
            'require_email',
            'verify_jwt',
            'email_claims',
            'jwt_public_key',
            'jwt_algorithm',
            'issuer',
            'issuer_validator',
            'token_auth_method',
            'post_logout_redirect_uri',
            'logout_token_replay_ttl',
            'cache_ttl',
            'clock_skew',
            'http_timeout',
            'http_connect_timeout',
            'proxy',
        ];
    }

    /**
     * Config defaults this provider derives or assumes, merged under the
     * configured values -- explicit config always wins. The hook a subclass
     * overrides to encode an IdP's trivia (deriving base_url from a `tenant`
     * or `realm`, pinning a quirk like the token auth method).
     */
    protected function configDefaults(array $config): array
    {
        return [];
    }

    public function setConfig(ConfigInterface $config)
    {
        $values = $config->get();
        $defaults = $this->configDefaults($values);

        if ($defaults !== []) {
            // The manager's ConfigRetriever materialises absent keys as null;
            // only a value the user actually set may beat a derived default.
            $values = array_replace(
                $defaults,
                array_filter($values, static fn ($value) => $value !== null),
            );

            $config = new Config(
                $values['client_id'] ?? null,
                $values['client_secret'] ?? null,
                $values['redirect'] ?? null,
                $values,
            );
        }

        return parent::setConfig($config);
    }

    /**
     * Read a config value from the raw array. getConfig() treats falsy values
     * as absent, which would make an explicit false or 0 unreachable.
     */
    protected function rawConfig(string $key): mixed
    {
        $config = $this->getConfig();

        return is_array($config) && isset($config[$key]) ? $config[$key] : null;
    }

    public function redirect(): RedirectResponse
    {
        $state = null;

        if ($this->usesState()) {
            $this->request->session()->put('state', $state = $this->getState());
        }

        if ($this->usesNonce()) {
            $this->request->session()->put(self::NONCE_SESSION_KEY, $this->getNonce());
        }

        if ($this->usesPKCE()) {
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

    public function getScopes(): array
    {
        $configured = $this->parseList($this->getConfig('scopes'));

        // Configured scopes replace the defaults (so an OP without `profile`
        // can be narrowed down) but keep anything added via fluent scopes().
        $scopes = $configured === []
            ? $this->scopes
            : array_merge($configured, array_diff($this->scopes, self::DEFAULT_SCOPES));

        // Without `openid` the OP returns no id_token at all.
        array_unshift($scopes, 'openid');

        return array_values(array_unique($scopes));
    }

    /**
     * Normalise a list given as an array, or a string separated by
     * whitespace and/or commas.
     *
     * @return string[]
     */
    protected function parseList(mixed $values): array
    {
        if ($values === null || $values === '' || $values === []) {
            return [];
        }

        $list = is_array($values)
            ? $values
            : preg_split('/[\s,]+/', (string) $values, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            array_map(static fn ($value) => is_string($value) ? trim($value) : null, $list ?: []),
            static fn (?string $value) => $value !== null && $value !== ''
        ));
    }

    protected function getTokenUrl(): string
    {
        return $this->getOpenIdConfig()['token_endpoint'];
    }

    protected function getUserInfoUrl(): string
    {
        return $this->getOpenIdConfig()['userinfo_endpoint'];
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            $this->getOpenIdConfig()['authorization_endpoint'],
            $state
        );
    }

    /**
     * The parent hardcodes '?', but discovered endpoints may already carry a
     * query (e.g. /authorize?realm=prod).
     */
    protected function buildAuthUrlFromBase($url, $state): string
    {
        return $this->appendQuery(
            $url,
            http_build_query($this->getCodeFields($state), '', '&', $this->encodingType)
        );
    }

    protected function appendQuery(string $url, string $query): string
    {
        if ($query === '') {
            return $url;
        }

        [$base, $fragment] = array_pad(explode('#', $url, 2), 2, null);

        $withQuery = $base.(str_contains($base, '?') ? '&' : '?').$query;

        return $fragment === null ? $withQuery : $withQuery.'#'.$fragment;
    }

    protected function getCodeFields($state = null): array
    {
        $fields = parent::getCodeFields($state);

        if ($this->usesNonce()) {
            $fields['nonce'] = $this->getCurrentNonce();
        }

        return $fields;
    }

    /**
     * Stateless flows have no session to carry the nonce between redirect and
     * callback, so it is skipped there -- safe for the code flow, where OIDC
     * Core 3.1.2.1 only requires a nonce for implicit and hybrid.
     */
    protected function usesNonce(): bool
    {
        if ($this->isStateless()) {
            return false;
        }

        $configured = $this->rawConfig('use_nonce');

        return $configured === null
            ? $this->usesNonce
            : filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }

    public function withoutNonce(): static
    {
        $this->usesNonce = false;

        return $this;
    }

    public function withoutPKCE(): static
    {
        $this->usesPKCE = false;

        return $this;
    }

    protected function getNonce(): string
    {
        return Str::random(40);
    }

    /**
     * Whether id_token signatures are verified. Back-channel logout tokens
     * are always verified regardless; see verifyLogoutToken().
     */
    protected function shouldVerifyJwt(): bool
    {
        $configured = $this->rawConfig('verify_jwt');

        return $configured === null
            ? $this->verifyJwt
            : filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }

    protected function getCacheTtl(): int
    {
        $ttl = $this->rawConfig('cache_ttl');

        return ($ttl === null || $ttl === '') ? 3600 : (int) $ttl;
    }

    protected function timeoutConfig(string $key, float $default): float
    {
        $configured = $this->rawConfig($key);

        return ($configured === null || $configured === '') ? $default : (float) $configured;
    }

    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $options = [
                'connect_timeout' => $this->timeoutConfig('http_connect_timeout', 5),
                'timeout'         => $this->timeoutConfig('http_timeout', 10),
            ];

            if ($proxy = $this->getConfig('proxy')) {
                $options[RequestOptions::PROXY] = $proxy;
            }

            $this->httpClient = new Client($options);
        }

        return $this->httpClient;
    }

    protected function getCurrentNonce(): ?string
    {
        return $this->request->hasSession()
            ? $this->request->session()->get(self::NONCE_SESSION_KEY)
            : null;
    }

    /**
     * Every endpoint is derived from base_url, so a plaintext issuer would
     * expose discovery, the JWKS and the token exchange to tampering.
     * Loopback hosts are exempt so local development works.
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

    protected function isLoopbackHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
            || str_ends_with($host, '.localhost');
    }

    protected function getOpenIdConfig(): array
    {
        if ($this->configurations === null) {
            $configUrl = $this->getBaseUrl().'/.well-known/openid-configuration';
            $cacheKey = 'openidconnect_discovery_'.md5($configUrl);

            $this->configurations = Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($configUrl) {
                try {
                    $response = $this->getHttpClient()->get($configUrl);

                    $document = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

                    if (! is_array($document) || $document === []) {
                        throw new InvalidArgumentException('the document is not a JSON object.');
                    }

                    return $document;
                } catch (Exception $e) {
                    throw new InvalidArgumentException('Unable to get the OIDC configuration from '.$configUrl.': '.$e->getMessage());
                }
            });
        }

        return $this->configurations;
    }

    protected function getJwks(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->jwksCacheKey());
        }

        return Cache::remember($this->jwksCacheKey(), $this->getCacheTtl(), function () use ($forceRefresh) {
            $config = $this->getOpenIdConfig();

            if (! isset($config['jwks_uri'])) {
                throw new InvalidArgumentException('JWKS URI not found in OIDC configuration');
            }

            // Bust any HTTP cache between us and the OP, not just our own.
            $options = $forceRefresh ? [
                RequestOptions::HEADERS => [
                    'Cache-Control' => 'no-cache',
                    'Pragma'        => 'no-cache',
                ],
            ] : [];

            try {
                $response = $this->getHttpClient()->get($config['jwks_uri'], $options);

                return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Unable to fetch JWKS: '.$e->getMessage());
            }
        });
    }

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

        // Some IdPs answer 200 with an error body, which Guzzle won't raise.
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

        // sub is the only claim the OP must return (OIDC Core 2).
        if (empty($claims['sub'])) {
            throw new InvalidArgumentException('JWT: Missing required sub claim.', 401);
        }

        if ($this->hasEmptyEmail($claims) && is_string($accessToken) && $accessToken !== '') {
            $claims = $this->mergeUserInfoClaims($claims, $this->getUserByToken($accessToken));
        }

        if ($this->requiresEmail() && $this->hasEmptyEmail($claims)) {
            throw new InvalidArgumentException('JWT: User has no email.', 401);
        }

        $this->user = $this->mapUserToObject($claims);

        if ($this->user instanceof User) {
            $this->user->setAccessTokenResponseBody($this->credentialsResponseBody);
        }

        return $this->user->setToken($accessToken)
            ->setRefreshToken($this->parseRefreshToken($tokenResponse))
            ->setExpiresIn($this->parseExpiresIn($tokenResponse))
            ->setApprovedScopes($this->parseApprovedScopes($tokenResponse));
    }

    /**
     * Decode an id_token from the token endpoint. Skipping the signature here
     * (verify_jwt false) is OIDC Core 3.1.3.7 step 6's TLS exemption: the
     * token answers a request we made over TLS, bound to this session by
     * nonce and PKCE. Contrast verifyLogoutToken(), which never gets that.
     */
    protected function decodeJWT(string $jwt, ?string $accessToken = null)
    {
        $header = $this->decodeJwtHeader($jwt);
        $alg = $header->alg ?? null;

        if ($this->shouldVerifyJwt()) {
            $payload = $this->verifyAndDecodeJWT($jwt, $alg);
        } else {
            [, $payloadSegment] = $this->jwtSegments($jwt);

            $payload = json_decode($this->base64UrlDecode($payloadSegment));

            if (! $payload instanceof stdClass) {
                throw new InvalidArgumentException('JWT: Failed to parse.', 401);
            }
        }

        $this->validateIdTokenClaims($payload, $alg, $accessToken);

        if ($this->usesNonce() && $this->request->hasSession()) {
            $this->request->session()->forget(self::NONCE_SESSION_KEY);
        }

        return $payload;
    }

    /**
     * The token's `alg` header is only ever checked against the RP's allow
     * list, never used to select the algorithm -- deriving it from the header
     * is the substitution attack of RFC 8725 2.1: claim `alg: HS256` and
     * HMAC-sign with the non-secret public key as the MAC secret.
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

        $previousLeeway = JWT::$leeway;

        try {
            JWT::$leeway = (int) ($this->getConfig('clock_skew') ?? 0);

            $publicKey = $this->getConfig('jwt_public_key');

            if ($publicKey) {
                if ($this->isHmacAlgorithm($alg) && $this->isAsymmetricKey($publicKey)) {
                    throw new InvalidArgumentException('HMAC algorithms cannot be used with an asymmetric public key.');
                }

                $decoded = JWT::decode($jwt, new Key($publicKey, $alg));
            } else {
                // A JWKS distributes public keys; an HMAC secret never is.
                if ($this->isHmacAlgorithm($alg)) {
                    throw new InvalidArgumentException('HMAC algorithms cannot be verified against a JWKS.');
                }

                $kid = $this->decodeJwtHeader($jwt)->kid ?? null;
                $jwks = $this->getJwks();

                // Unknown kid: the OP has likely rotated keys since we cached.
                if ($kid !== null && ! $this->jwksContainsKid($jwks, $kid)) {
                    $jwks = $this->getJwks(forceRefresh: true);
                }

                $decoded = $this->decodeAgainstJwks($jwt, $jwks, $kid, $alg);
            }

            return json_decode(json_encode($decoded));
        } catch (Exception $e) {
            throw new InvalidArgumentException('JWT: Verification failed - '.$e->getMessage(), 401);
        } finally {
            JWT::$leeway = $previousLeeway;
        }
    }

    protected function decodeAgainstJwks(string $jwt, array $jwks, ?string $kid, string $alg)
    {
        if ($kid !== null) {
            return JWT::decode($jwt, JWK::parseKeySet($jwks, $alg));
        }

        $decoded = $this->decodeAgainstEachKey($jwt, $jwks, $alg);

        if ($decoded === null) {
            $decoded = $this->decodeAgainstEachKey($jwt, $this->getJwks(forceRefresh: true), $alg);
        }

        if ($decoded === null) {
            throw new InvalidArgumentException('no key in the JWKS verifies this token.');
        }

        return $decoded;
    }

    protected function decodeAgainstEachKey(string $jwt, array $jwks, string $alg)
    {
        foreach (JWK::parseKeySet($jwks, $alg) as $key) {
            try {
                return JWT::decode($jwt, $key);
            } catch (SignatureInvalidException) {
                continue;
            }
        }

        return null;
    }

    /**
     * Pinned `jwt_algorithm` config, else the OP's advertised
     * `id_token_signing_alg_values_supported`, else RS256. Never `none`.
     *
     * @return string[]
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

    protected function isHmacAlgorithm(string $alg): bool
    {
        return str_starts_with(strtoupper($alg), 'HS');
    }

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
        [$headerSegment] = $this->jwtSegments($jwt);

        $header = json_decode($this->base64UrlDecode($headerSegment));

        if (! $header instanceof stdClass) {
            throw new InvalidArgumentException('JWT: Failed to parse header.', 401);
        }

        return $header;
    }

    /**
     * Destructuring explode() directly would produce a TypeError -- not an
     * Exception -- on a token without dots, escaping catch (Exception).
     *
     * @return string[]
     */
    protected function jwtSegments(string $jwt): array
    {
        $segments = explode('.', $jwt);

        if (count($segments) !== 3) {
            throw new InvalidArgumentException('JWT: Malformed token, expected three segments.', 401);
        }

        return $segments;
    }

    protected function validateIdTokenClaims($payload, ?string $alg, ?string $accessToken): void
    {
        if ($this->isInvalidNonce($payload->nonce ?? null)) {
            throw new InvalidArgumentException('JWT: Contains an invalid nonce.', 401);
        }

        $this->validateIssuerClaim($payload, 'JWT');

        $aud = $payload->aud ?? null;
        $audList = is_array($aud) ? $aud : [$aud];
        if (! in_array($this->clientId, $audList, true)) {
            throw new InvalidArgumentException('JWT: Invalid audience.', 401);
        }

        // OIDC Core 3.1.3.7 step 4.
        if (is_array($aud) && count($aud) > 1 && ! isset($payload->azp)) {
            throw new InvalidArgumentException('JWT: Multiple audiences require an azp claim.', 401);
        }

        // Step 5, unconditionally: a token naming us as audience but
        // authorized to a different party is not ours to accept.
        if (isset($payload->azp) && $payload->azp !== $this->clientId) {
            throw new InvalidArgumentException('JWT: Invalid authorized party (azp).', 401);
        }

        if ($accessToken !== null && isset($payload->at_hash) && $alg) {
            $this->validateAtHash($payload->at_hash, $accessToken, $alg);
        }

        $this->validateTimeClaims($payload);
    }

    /**
     * The expected issuer is the `issuer` config, else the discovery
     * document's; the comparison itself is delegated to the pluggable
     * validator.
     */
    protected function validateIssuerClaim($payload, string $context): void
    {
        $expectedIssuer = $this->getConfig('issuer') ?: ($this->getOpenIdConfig()['issuer'] ?? null);

        if ($expectedIssuer === null) {
            return;
        }

        $validator = $this->issuerValidator();

        $valid = $validator instanceof IssuerValidator
            ? $validator->validate($expectedIssuer, $payload)
            : (bool) $validator($expectedIssuer, $payload);

        if (! $valid) {
            throw new InvalidArgumentException($context.': Invalid issuer.', 401);
        }
    }

    /**
     * @param  IssuerValidator|callable(string, stdClass): bool  $validator
     */
    public function validateIssuerUsing(IssuerValidator|callable $validator): static
    {
        $this->issuerValidator = $validator;

        return $this;
    }

    /**
     * @return IssuerValidator|callable
     */
    protected function issuerValidator()
    {
        if ($this->issuerValidator !== null) {
            return $this->issuerValidator;
        }

        $class = $this->getConfig('issuer_validator');

        if ($class) {
            $validator = Container::getInstance()->make($class);

            if (! $validator instanceof IssuerValidator) {
                throw new InvalidArgumentException(
                    'OIDC: issuer_validator ['.$class.'] must implement '.IssuerValidator::class.'.'
                );
            }

            return $this->issuerValidator = $validator;
        }

        return $this->issuerValidator = new DefaultIssuerValidator;
    }

    /**
     * Runs in both the verified and unverified paths. exp is required, not
     * optional: firebase/php-jwt gates its own expiry check on isset(), so a
     * token that simply omits the claim would otherwise never expire.
     *
     * @param  bool  $requireIat  Back-Channel Logout 2.4 requires iat too.
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

    /**
     * at_hash is the left-most half of the access token's hash, base64url
     * encoded (OIDC Core 3.1.3.6).
     */
    protected function validateAtHash(string $atHash, string $accessToken, string $alg): void
    {
        $map = [
            'RS256' => 'sha256', 'RS384' => 'sha384', 'RS512' => 'sha512',
            'PS256' => 'sha256', 'PS384' => 'sha384', 'PS512' => 'sha512',
            'ES256' => 'sha256', 'ES384' => 'sha384', 'ES512' => 'sha512',
            'HS256' => 'sha256', 'HS384' => 'sha384', 'HS512' => 'sha512',
            'EdDSA' => 'sha512',
        ];

        if (! isset($map[$alg])) {
            throw new InvalidArgumentException('JWT: Cannot validate at_hash for algorithm ['.$alg.'].', 401);
        }

        $digest = hash($map[$alg], $accessToken, true);
        $expected = $this->base64UrlEncode(substr($digest, 0, intdiv(strlen($digest), 2)));

        if (! hash_equals($expected, $atHash)) {
            throw new InvalidArgumentException('JWT: at_hash mismatch.', 401);
        }
    }

    /**
     * Strict mode, so attacker-supplied garbage fails here as a parse error
     * instead of surfacing later as a baffling claim mismatch.
     */
    private function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(
            str_pad(strtr($data, '-_', '+/'), intdiv(strlen($data) + 3, 4) * 4, '=', STR_PAD_RIGHT),
            true
        );

        if ($decoded === false) {
            throw new InvalidArgumentException('JWT: Malformed base64url segment.', 401);
        }

        return $decoded;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function isInvalidNonce($nonce): bool
    {
        if (! $this->usesNonce()) {
            return false;
        }

        return ! (is_string($nonce) && strlen($nonce) > 0 && $nonce === $this->getCurrentNonce());
    }

    /**
     * Merged over (not substituted for) the id_token claims, so claims only
     * the id_token carried survive. OIDC Core 5.3.2 requires the subs to
     * match exactly before the response may be used.
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

    protected function requiresEmail(): bool
    {
        $configured = $this->rawConfig('require_email');

        return $configured === null
            ? $this->requireEmail
            : filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * The claims consulted for the user's email, in order. Configurable
     * because not every IdP puts the login email in `email` -- Entra returns
     * the contact-info email there (often empty or unrelated) and the login
     * identity in `preferred_username`.
     *
     * @return string[]
     */
    protected function emailClaims(): array
    {
        $configured = $this->parseList($this->getConfig('email_claims'));

        return $configured === [] ? ['email'] : $configured;
    }

    protected function resolveEmail(array|stdClass $claims): ?string
    {
        $claims = (array) $claims;

        foreach ($this->emailClaims() as $claim) {
            $value = $claims[$claim] ?? null;

            if (is_string($value) && $value !== '' && $this->acceptableEmail($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Whether a candidate claim value may be used as the email. The base
     * accepts anything, since a configured claim list is the operator's
     * explicit choice; subclasses tighten this where a claim has no fixed
     * format (Entra's preferred_username can be a phone number).
     */
    protected function acceptableEmail(string $value): bool
    {
        return true;
    }

    protected function hasEmptyEmail(array|stdClass $payload): bool
    {
        return $this->resolveEmail($payload) === null;
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'          => $user['sub'] ?? null,
            'email'       => $this->resolveEmail($user),
            'name'        => $user['name'] ?? null,
            'nickname'    => $user['nickname'] ?? null,
            'given_name'  => $user['given_name'] ?? null,
            'family_name' => $user['family_name'] ?? null,
            'idp'         => $user['idp'] ?? null,
            'role'        => $user['role'] ?? null,
            'groups'      => $user['groups'] ?? null,
        ]);
    }

    public function getAccessTokenResponse($code)
    {
        $fields = array_merge(
            $this->getTokenFields($code),
            ['grant_type' => 'authorization_code']
        );

        $response = $this->getHttpClient()->post($this->getTokenUrl(), $this->tokenRequestOptions($fields));

        try {
            return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Token endpoint returned a non-JSON response: '.$e->getMessage());
        }
    }

    protected function tokenRequestOptions(array $fields): array
    {
        $options = [RequestOptions::HEADERS => ['Accept' => 'application/json']];

        if ($this->resolveTokenAuthMethod() === 'client_secret_basic') {
            // RFC 6749 2.3.1: each half is form-urlencoded before base64.
            // Guzzle's `auth` option encodes the raw pair, which breaks any
            // secret containing +, /, = or a space.
            $options[RequestOptions::HEADERS]['Authorization'] = 'Basic '.base64_encode(
                urlencode($this->clientId).':'.urlencode($this->clientSecret)
            );

            unset($fields['client_id'], $fields['client_secret']);
        }

        $options[RequestOptions::FORM_PARAMS] = $fields;

        return $options;
    }

    protected function resolveTokenAuthMethod(): string
    {
        $configured = $this->getConfig('token_auth_method');
        if ($configured) {
            return $configured;
        }

        $supported = $this->getOpenIdConfig()['token_endpoint_auth_methods_supported'] ?? [];

        return in_array('client_secret_basic', $supported, true)
            ? 'client_secret_basic'
            : 'client_secret_post';
    }

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
     */
    public function logout(?string $idToken = null, ?string $postLogoutRedirectUri = null, array $extra = []): RedirectResponse
    {
        $config = $this->getOpenIdConfig();

        if (empty($config['end_session_endpoint'])) {
            throw new InvalidArgumentException('Provider does not advertise an end_session_endpoint.');
        }

        // A state is only sent when a session can store it for
        // validateLogoutState() to check later.
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
     * The stored state is pulled, so it is good for exactly one round trip.
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
     * Revoke a token per RFC 7009. Returns false rather than throwing for a
     * non-2xx answer, since a conforming server returns 200 even for an
     * already-revoked token and callers should not lose a logout over it.
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

        $options[RequestOptions::HTTP_ERRORS] = false;

        $response = $this->getHttpClient()->post($config['revocation_endpoint'], $options);

        return in_array($response->getStatusCode(), [200, 204], true);
    }

    /**
     * Verify a back-channel logout token (openid-connect-backchannel-1_0).
     *
     * Unlike an id_token, this arrives unsolicited on an unauthenticated
     * endpoint -- no session nonce, no PKCE, no TLS exemption -- so the
     * signature is always verified regardless of `verify_jwt`. Replay of a
     * `jti` is refused per section 2.6; set `logout_token_replay_ttl` to 0 to
     * handle that yourself. Returns the claims so the caller can destroy the
     * sessions matching `sid` / `sub`.
     */
    public function verifyLogoutToken(string $logoutToken): array
    {
        $header = $this->decodeJwtHeader($logoutToken);
        $alg = $header->alg ?? null;

        $payload = $this->verifyAndDecodeJWT($logoutToken, $alg);

        $this->validateIssuerClaim($payload, 'Logout token');

        $aud = $payload->aud ?? null;
        $audList = is_array($aud) ? $aud : [$aud];
        if (! in_array($this->clientId, $audList, true)) {
            throw new InvalidArgumentException('Logout token: invalid audience.', 401);
        }

        $this->validateTimeClaims($payload, requireIat: true);

        if (empty($payload->jti ?? null)) {
            throw new InvalidArgumentException('Logout token: missing jti.', 401);
        }

        // A nonce marks an id_token; refusing it prevents token confusion.
        if (isset($payload->nonce)) {
            throw new InvalidArgumentException('Logout token: must not contain a nonce.', 401);
        }

        $events = (array) ($payload->events ?? []);
        if (! array_key_exists(self::BACKCHANNEL_LOGOUT_EVENT, $events)) {
            throw new InvalidArgumentException('Logout token: missing backchannel-logout event.', 401);
        }

        if (empty($payload->sub ?? null) && empty($payload->sid ?? null)) {
            throw new InvalidArgumentException('Logout token: must contain sub and/or sid.', 401);
        }

        // Claimed last, once the token is known genuine, so a forged token
        // cannot burn the jti of a legitimate one still in flight.
        $this->assertLogoutTokenNotReplayed((string) $payload->jti, $payload->exp ?? null);

        return (array) $payload;
    }

    protected function assertLogoutTokenNotReplayed(string $jti, int|float|null $expiresAt = null): void
    {
        $ttl = $this->logoutTokenReplayTtl($expiresAt);

        if ($ttl <= 0) {
            return;
        }

        $key = 'openidconnect_logout_jti_'.md5($this->getBaseUrl().'|'.$this->clientId.'|'.$jti);

        // Cache::add writes only when absent, atomically; has()/put() would
        // let two deliveries of the same token race past each other.
        if (! Cache::add($key, true, $ttl)) {
            throw new InvalidArgumentException('Logout token: already used.', 401);
        }
    }

    /**
     * A jti is remembered until the token itself has expired (plus skew);
     * past that point the token is refused on its own merits.
     */
    protected function logoutTokenReplayTtl(int|float|null $expiresAt = null): int
    {
        $configured = $this->rawConfig('logout_token_replay_ttl');

        if ($configured !== null && $configured !== '') {
            return (int) $configured;
        }

        if (is_numeric($expiresAt)) {
            $remaining = (int) ceil((float) $expiresAt - time());
            $skew = (int) ($this->getConfig('clock_skew') ?? 0);

            return max(60, $remaining + $skew + 60);
        }

        return 900;
    }
}
