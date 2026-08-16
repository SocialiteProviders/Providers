<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use ReflectionMethod;
use ReflectionProperty;
use SocialiteProviders\OpenIDConnect\IssuerValidators\EntraIssuerValidator;
use SocialiteProviders\OpenIDConnect\OpenIDConnectServiceProvider;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;
use SocialiteProviders\Tests\OpenIDConnect\Support\CustomProvider;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class MultiConnectionTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('oidc.connections', [
            'keycloak' => [
                'base_url'      => 'https://kc.test/realms/main',
                'client_id'     => 'kc-client',
                'client_secret' => 'kc-secret',
                'redirect'      => 'https://app.test/kc/callback',
            ],
            'authentik' => [
                'base_url'      => 'https://ak.test/application/o/app',
                'client_id'     => 'ak-client',
                'client_secret' => 'ak-secret',
                'redirect'      => 'https://app.test/ak/callback',
            ],
            'entra' => [
                'provider'      => 'entra',
                'tenant'        => 'contoso',
                'client_id'     => 'ms-client',
                'client_secret' => 'ms-secret',
                'redirect'      => 'https://app.test/ms/callback',
            ],
            'custom' => [
                'provider'      => CustomProvider::class,
                'base_url'      => 'https://custom.test',
                'client_id'     => 'custom-client',
                'client_secret' => 'custom-secret',
                'redirect'      => 'https://app.test/custom/callback',
            ],
        ]);

        $app['config']->set('services.openidconnect', [
            'base_url'      => 'https://single.test',
            'client_id'     => 'single-client',
            'client_secret' => 'single-secret',
            'redirect'      => 'https://app.test/callback',
        ]);
    }

    private function clientIdOf(Provider $provider): string
    {
        return (new ReflectionProperty($provider, 'clientId'))->getValue($provider);
    }

    private function configValueOf(Provider $provider, string $key): mixed
    {
        return (new ReflectionMethod($provider, 'getConfig'))->invoke($provider, $key);
    }

    public function test_each_connection_is_mirrored_into_the_services_config(): void
    {
        $this->assertSame('kc-client', config('services.oidc_keycloak.client_id'));
        $this->assertSame('ak-client', config('services.oidc_authentik.client_id'));
    }

    public function test_each_connection_registers_its_own_driver_with_its_own_config(): void
    {
        $keycloak = Socialite::driver('oidc_keycloak');
        $authentik = Socialite::driver('oidc_authentik');

        $this->assertInstanceOf(Provider::class, $keycloak);
        $this->assertInstanceOf(Provider::class, $authentik);
        $this->assertSame('kc-client', $this->clientIdOf($keycloak));
        $this->assertSame('ak-client', $this->clientIdOf($authentik));
        $this->assertSame('https://kc.test/realms/main', $this->configValueOf($keycloak, 'base_url'));
        $this->assertSame('https://ak.test/application/o/app', $this->configValueOf($authentik, 'base_url'));
    }

    public function test_connections_redirect_to_their_own_issuers(): void
    {
        foreach ([
            'oidc_keycloak'  => ['https://kc.test/realms/main', 'kc-client'],
            'oidc_authentik' => ['https://ak.test/application/o/app', 'ak-client'],
        ] as $driver => [$baseUrl, $clientId]) {
            $provider = Socialite::driver($driver);

            $request = Request::create('https://app.test/login', 'GET');
            $request->setLaravelSession(new Store('testing', new ArraySessionHandler(120)));
            $provider->setRequest($request);

            $provider->setHttpClient(new Client([
                'handler' => HandlerStack::create(new MockHandler([
                    new Response(200, [], json_encode([
                        'authorization_endpoint' => $baseUrl.'/authorize',
                        'issuer'                 => $baseUrl,
                    ])),
                ])),
            ]));

            $url = $provider->redirect()->getTargetUrl();

            $this->assertStringStartsWith($baseUrl.'/authorize?', $url);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $this->assertSame($clientId, $query['client_id']);
        }
    }

    public function test_a_provider_shorthand_resolves_to_the_built_in_class_with_derived_config(): void
    {
        $entra = Socialite::driver('oidc_entra');

        $this->assertInstanceOf(EntraProvider::class, $entra);
        $this->assertSame(
            'https://login.microsoftonline.com/contoso/v2.0',
            $this->configValueOf($entra, 'base_url'),
        );
        $this->assertSame(
            EntraIssuerValidator::class,
            $this->configValueOf($entra, 'issuer_validator'),
        );
    }

    public function test_a_connection_can_swap_in_a_provider_subclass(): void
    {
        $this->assertInstanceOf(CustomProvider::class, Socialite::driver('oidc_custom'));
    }

    public function test_the_single_connection_services_fallback_still_registers(): void
    {
        $provider = Socialite::driver('openidconnect');

        $this->assertInstanceOf(Provider::class, $provider);
        $this->assertSame('single-client', $this->clientIdOf($provider));
    }

    public function test_an_unregistered_driver_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Socialite::driver('oidc_nope');
    }

    public function test_an_invalid_provider_class_is_refused_at_registration(): void
    {
        config(['oidc.connections' => [
            'bad' => [
                'provider'  => \stdClass::class,
                'base_url'  => 'https://bad.test',
            ],
        ]]);

        $serviceProvider = new OpenIDConnectServiceProvider($this->app);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('extending');

        (new ReflectionMethod($serviceProvider, 'connections'))->invoke($serviceProvider);
    }

    public function test_an_unknown_provider_shorthand_lists_the_known_ones(): void
    {
        config(['oidc.connections' => [
            'bad' => ['provider' => 'nope', 'base_url' => 'https://bad.test'],
        ]]);

        $serviceProvider = new OpenIDConnectServiceProvider($this->app);

        try {
            (new ReflectionMethod($serviceProvider, 'connections'))->invoke($serviceProvider);
            $this->fail('Expected the unknown shorthand to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('[nope]', $e->getMessage());
            $this->assertStringContainsString('entra', $e->getMessage());
            $this->assertStringContainsString('keycloak', $e->getMessage());
        }
    }
}
