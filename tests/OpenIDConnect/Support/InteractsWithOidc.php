<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Support;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Builds providers wired to a Guzzle MockHandler, and signed test tokens.
 *
 * Key slots: slot 1 is "the OP's current key", slot 2 is "the key the OP
 * rotated to". Both are real 2048-bit RSA keys, generated once per process.
 */
trait InteractsWithOidc
{
    protected static array $rsaKeys = [];

    /** @var array<int, array{request: RequestInterface, response: ?ResponseInterface}> */
    protected array $httpHistory = [];

    protected ?MockHandler $mockHandler = null;

    protected static string $opBaseUrl = 'https://op.test';

    protected static string $opClientId = 'client-id';

    protected static string $opClientSecret = 'client-secret';

    protected static string $opRedirect = 'https://app.test/callback';

    protected static string $opNonce = 'test-nonce-value';

    protected static string $opState = 'test-state-value';

    /**
     * @return array{private: string, public: string, n: string, e: string}
     */
    protected static function rsaKey(int $slot = 1): array
    {
        if (! isset(static::$rsaKeys[$slot])) {
            $resource = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            openssl_pkey_export($resource, $privatePem);
            $details = openssl_pkey_get_details($resource);

            static::$rsaKeys[$slot] = [
                'private' => $privatePem,
                'public'  => $details['key'],
                'n'       => static::base64Url($details['rsa']['n']),
                'e'       => static::base64Url($details['rsa']['e']),
            ];
        }

        return static::$rsaKeys[$slot];
    }

    protected static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function jwk(string $kid = 'kid-1', int $slot = 1): array
    {
        $key = static::rsaKey($slot);

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $kid,
            'n'   => $key['n'],
            'e'   => $key['e'],
        ];
    }

    /**
     * @param  array  $keys  list of jwk() arrays; defaults to slot 1 under kid-1
     */
    protected function jwksDocument(?array $keys = null): array
    {
        return ['keys' => $keys ?? [$this->jwk()]];
    }

    protected function idTokenClaims(array $overrides = []): array
    {
        return array_merge([
            'iss'   => static::$opBaseUrl,
            'aud'   => static::$opClientId,
            'sub'   => 'user-123',
            'email' => 'user@example.com',
            'name'  => 'Test User',
            'exp'   => time() + 3600,
            'iat'   => time(),
            'nonce' => static::$opNonce,
        ], $overrides);
    }

    protected function encodeToken(array $claims, string $kid = 'kid-1', string $alg = 'RS256', int $slot = 1): string
    {
        return JWT::encode($claims, static::rsaKey($slot)['private'], $alg, $kid);
    }

    protected function encodeTokenWithoutKid(array $claims, string $alg = 'RS256', int $slot = 1): string
    {
        return JWT::encode($claims, static::rsaKey($slot)['private'], $alg);
    }

    /**
     * A structurally valid JWT whose signature verifies against nothing.
     */
    protected function unsignedToken(array $claims, array $header = ['alg' => 'RS256', 'typ' => 'JWT']): string
    {
        return static::base64Url(json_encode($header))
            .'.'.static::base64Url(json_encode($claims))
            .'.'.static::base64Url('not-a-signature');
    }

    protected function atHashFor(string $accessToken, string $algo = 'sha256'): string
    {
        $digest = hash($algo, $accessToken, true);

        return static::base64Url(substr($digest, 0, intdiv(strlen($digest), 2)));
    }

    protected function discoveryDocument(array $overrides = []): array
    {
        return array_merge([
            'issuer'                                => static::$opBaseUrl,
            'authorization_endpoint'                => static::$opBaseUrl.'/authorize',
            'token_endpoint'                        => static::$opBaseUrl.'/token',
            'userinfo_endpoint'                     => static::$opBaseUrl.'/userinfo',
            'jwks_uri'                              => static::$opBaseUrl.'/jwks',
            'end_session_endpoint'                  => static::$opBaseUrl.'/logout',
            'revocation_endpoint'                   => static::$opBaseUrl.'/revoke',
            'id_token_signing_alg_values_supported' => ['RS256'],
        ], $overrides);
    }

    protected function jsonResponse(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    protected function tokenEndpointResponse(string $idToken, array $overrides = []): Response
    {
        return $this->jsonResponse(array_merge([
            'access_token'  => 'the-access-token',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
            'refresh_token' => 'the-refresh-token',
            'scope'         => 'openid email profile',
            'id_token'      => $idToken,
        ], $overrides));
    }

    protected function sessionStore(): Store
    {
        return new Store('testing', new ArraySessionHandler(120));
    }

    protected function callbackRequest(?array $query = null, bool $withSession = true, ?array $session = null): Request
    {
        $query ??= ['code' => 'the-auth-code', 'state' => static::$opState];

        $request = Request::create('https://app.test/callback', 'GET', $query);

        if ($withSession) {
            $store = $this->sessionStore();

            foreach ($session ?? ['state' => static::$opState, Provider::NONCE_SESSION_KEY => static::$opNonce] as $key => $value) {
                $store->put($key, $value);
            }

            $request->setLaravelSession($store);
        }

        return $request;
    }

    protected function redirectRequest(bool $withSession = true): Request
    {
        $request = Request::create('https://app.test/login', 'GET');

        if ($withSession) {
            $request->setLaravelSession($this->sessionStore());
        }

        return $request;
    }

    /**
     * @param  array  $config  additional provider config, merged over base_url
     * @param  array  $responses  Guzzle MockHandler queue
     * @param  class-string<Provider>  $providerClass
     */
    protected function makeProvider(array $config = [], array $responses = [], ?Request $request = null, string $providerClass = Provider::class): Provider
    {
        $request ??= $this->callbackRequest();

        $config = array_merge(['base_url' => static::$opBaseUrl], $config);

        $provider = new $providerClass(
            $request,
            static::$opClientId,
            static::$opClientSecret,
            static::$opRedirect,
        );

        $provider->setConfig(new Config(
            static::$opClientId,
            static::$opClientSecret,
            static::$opRedirect,
            $config,
        ));

        $this->mockHandler = new MockHandler($responses);

        $stack = HandlerStack::create($this->mockHandler);
        $this->httpHistory = [];
        $stack->push(Middleware::history($this->httpHistory));

        $provider->setHttpClient(new Client(['handler' => $stack]));

        return $provider;
    }

    /**
     * The standard happy-path queue: discovery, token exchange, JWKS.
     */
    protected function happyPathResponses(?array $claims = null, string $kid = 'kid-1', array $tokenOverrides = []): array
    {
        $claims ??= $this->idTokenClaims();

        return [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->encodeToken($claims, $kid), $tokenOverrides),
            $this->jsonResponse($this->jwksDocument()),
        ];
    }

    /**
     * Requests recorded by the history middleware, as "METHOD path" strings.
     *
     * @return string[]
     */
    protected function requestedPaths(): array
    {
        return array_map(
            static fn (array $entry) => $entry['request']->getMethod().' '.$entry['request']->getUri()->getPath(),
            $this->httpHistory,
        );
    }

    protected function lastRequestTo(string $path): ?RequestInterface
    {
        foreach (array_reverse($this->httpHistory) as $entry) {
            if ($path === $entry['request']->getUri()->getPath()) {
                return $entry['request'];
            }
        }

        return null;
    }

    protected function queryOf(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }
}
