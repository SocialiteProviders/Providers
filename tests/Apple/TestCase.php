<?php

namespace SocialiteProviders\Tests\Apple;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use SocialiteProviders\Apple\Provider;
use SocialiteProviders\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const KEY_ID = 'test-key';

    protected string $privateKey;

    /**
     * @var array<string, mixed>
     */
    protected array $jwks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindCache();
        $this->makeKeyPair();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    /**
     * The provider caches the JWKS through the Cache facade, which needs a
     * container behind it. An array store keeps each test isolated.
     */
    private function bindCache(): void
    {
        $container = new Container;
        $container->instance('cache', new Repository(new ArrayStore));

        Container::setInstance($container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    protected function provider(): string
    {
        return Provider::class;
    }

    /**
     * A provider whose JWKS fetch is served from the generated key pair.
     */
    protected function makeAppleProvider(?Request $request = null): Provider
    {
        $provider = new AppleProviderStub(
            $request ?? $this->makeRequest(),
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI
        );

        $provider->setJwkHttpClient($this->makeJwkClient());

        return $provider;
    }

    /**
     * A Guzzle client that replays Apple's JWKS endpoint.
     */
    protected function makeJwkClient(): Client
    {
        return new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['Content-Type' => 'application/json'], json_encode($this->jwks)),
            ])),
        ]);
    }

    /**
     * Build an Apple-shaped identity token signed with the generated key.
     *
     * @param  array<string, mixed>  $claims
     */
    protected function identityToken(array $claims = []): string
    {
        $claims = array_merge([
            'iss'   => Provider::URL,
            'sub'   => 'apple-user-id',
            'aud'   => static::CLIENT_ID,
            'email' => 'user@example.com',
        ], $claims);

        $now = new \DateTimeImmutable;

        $builder = (new Builder(new JoseEncoder, ChainedFormatter::default()))
            ->issuedAt($now->modify('-5 seconds'))
            ->canOnlyBeUsedAfter($now->modify('-5 seconds'))
            ->expiresAt($now->modify('+5 minutes'))
            ->withHeader('kid', static::KEY_ID);

        foreach ((array) $claims['aud'] as $audience) {
            $builder = $builder->permittedFor($audience);
        }

        $builder = $builder->issuedBy($claims['iss'])->relatedTo($claims['sub']);

        foreach ($claims as $name => $value) {
            if (! in_array($name, ['iss', 'sub', 'aud'], true)) {
                $builder = $builder->withClaim($name, $value);
            }
        }

        return $builder->getToken(new Sha256, InMemory::plainText($this->privateKey))->toString();
    }

    /**
     * A token with no aud claim at all, which the builder cannot express.
     *
     * @param  array<string, mixed>  $claims
     */
    protected function identityTokenWithoutAudience(array $claims = []): string
    {
        $token = $this->identityToken($claims);

        [$headers, $payload, $signature] = explode('.', $token);

        $decoded = json_decode($this->base64UrlDecode($payload), true);
        unset($decoded['aud']);

        // Re-signing is unnecessary: aud is checked alongside the signature, and
        // asserting on the audience violation would hide a signature failure.
        return $headers.'.'.$this->base64UrlEncode(json_encode($decoded)).'.'.$signature;
    }

    private function makeKeyPair(): void
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $privateKey));

        $details = openssl_pkey_get_details($key);

        $this->assertIsArray($details);

        $this->privateKey = $privateKey;
        $this->jwks = [
            'keys' => [[
                'kty' => 'RSA',
                'kid' => static::KEY_ID,
                'use' => 'sig',
                'alg' => 'RS256',
                'n'   => $this->base64UrlEncode($details['rsa']['n']),
                'e'   => $this->base64UrlEncode($details['rsa']['e']),
            ]],
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
