<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use ArrayAccess;
use Firebase\JWT\JWT;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const BASE_URL = 'https://idp.test';

    protected const CLIENT_ID = 'client-id';

    protected const CLIENT_SECRET = 'client-secret';

    protected const REDIRECT = 'https://app.test/callback';

    protected const KID = 'test-key-1';

    /**
     * Generating an RSA keypair is expensive, so share one across the suite.
     *
     * @var array{private: string, public: string}|null
     */
    private static ?array $keypair = null;

    protected string $privateKey;

    protected string $publicKey;

    protected function provider(): string
    {
        return ProviderStub::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootCacheFacade();

        if (self::$keypair === null) {
            $resource = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            openssl_pkey_export($resource, $private);

            self::$keypair = [
                'private' => $private,
                'public'  => openssl_pkey_get_details($resource)['key'],
            ];
        }

        $this->privateKey = self::$keypair['private'];
        $this->publicKey = self::$keypair['public'];
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    /**
     * The provider reaches for the Cache facade (discovery, JWKS, logout token
     * replay). Rather than boot a framework for it, back the facade with an
     * array store held in a throwaway container, fresh for every test.
     */
    private function bootCacheFacade(): void
    {
        $container = new class(['cache' => new Repository(new ArrayStore)]) implements ArrayAccess
        {
            public function __construct(private array $bindings) {}

            public function offsetExists(mixed $offset): bool
            {
                return isset($this->bindings[$offset]);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this->bindings[$offset] ?? null;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                $this->bindings[$offset] = $value;
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($this->bindings[$offset]);
            }
        };

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    /**
     * A minimal discovery document. `configurations` is public on the provider,
     * so seeding it avoids any HTTP round trip.
     */
    protected function discoveryDocument(array $overrides = []): array
    {
        return array_merge([
            'issuer'                                => self::BASE_URL,
            'authorization_endpoint'                => self::BASE_URL.'/authorize',
            'token_endpoint'                        => self::BASE_URL.'/token',
            'userinfo_endpoint'                     => self::BASE_URL.'/userinfo',
            'jwks_uri'                              => self::BASE_URL.'/jwks',
            'end_session_endpoint'                  => self::BASE_URL.'/logout',
            'revocation_endpoint'                   => self::BASE_URL.'/revoke',
            'id_token_signing_alg_values_supported' => ['RS256'],
        ], $overrides);
    }

    protected function request(array $query = [], array $session = []): Request
    {
        $request = Request::create(self::REDIRECT, 'GET', $query);

        $store = new Store('test-session', new ArraySessionHandler(60));
        foreach ($session as $key => $value) {
            $store->put($key, $value);
        }

        $request->setLaravelSession($store);

        return $request;
    }

    protected function oidcProvider(array $config = [], ?Request $request = null, ?array $discovery = null): ProviderStub
    {
        $config = array_merge([
            'base_url'   => self::BASE_URL,
            'verify_jwt' => true,
        ], $config);

        $class = $this->provider();

        $provider = new $class(
            $request ?? $this->request(),
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            self::REDIRECT
        );

        $provider->setConfig(new Config(self::CLIENT_ID, self::CLIENT_SECRET, self::REDIRECT, $config));
        $provider->configurations = $discovery ?? $this->discoveryDocument();

        return $provider;
    }

    /**
     * Seed the JWKS cache so the JWKS verification path needs no HTTP.
     */
    protected function seedJwks(?array $jwks = null): void
    {
        Cache::put('openidconnect_jwks_'.md5(self::BASE_URL), $jwks ?? $this->jwks(), 3600);
    }

    protected function jwks(): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_public($this->publicKey));

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => self::KID,
                    'n'   => $this->base64UrlEncode($details['rsa']['n']),
                    'e'   => $this->base64UrlEncode($details['rsa']['e']),
                ],
            ],
        ];
    }

    /**
     * Mint a legitimately signed id_token.
     */
    protected function idToken(array $claims = [], string $alg = 'RS256', mixed $key = null): string
    {
        return JWT::encode(
            $this->claims($claims),
            $key ?? $this->privateKey,
            $alg,
            self::KID
        );
    }

    protected function claims(array $overrides = []): array
    {
        return array_merge([
            'iss'   => self::BASE_URL,
            'aud'   => self::CLIENT_ID,
            'sub'   => 'user-1',
            'email' => 'user@example.test',
            'iat'   => time(),
            'exp'   => time() + 3600,
        ], $overrides);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
