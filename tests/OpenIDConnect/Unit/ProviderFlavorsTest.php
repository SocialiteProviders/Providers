<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use Illuminate\Http\Request;
use ReflectionMethod;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\OpenIDConnect\IssuerValidators\EntraIssuerValidator;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\OpenIDConnect\Providers\Auth0Provider;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;
use SocialiteProviders\OpenIDConnect\Providers\GoogleProvider;
use SocialiteProviders\OpenIDConnect\Providers\KeycloakProvider;
use SocialiteProviders\OpenIDConnect\Providers\OktaProvider;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class TenantPathProvider extends Provider
{
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['tenant']);
    }

    protected function configDefaults(array $config): array
    {
        return [
            'base_url'   => 'https://sso.mycorp.example/tenants/'.($config['tenant'] ?? 'default'),
            'clock_skew' => 30,
        ];
    }
}

class ProviderFlavorsTest extends TestCase
{
    /**
     * @param  class-string<Provider>  $class
     */
    private function flavor(string $class, array $config): Provider
    {
        $provider = new $class(
            Request::create('https://app.test/login'),
            'the-client',
            'the-secret',
            'https://app.test/callback',
        );

        $provider->setConfig(new Config('the-client', 'the-secret', 'https://app.test/callback', $config));

        return $provider;
    }

    private function configOf(Provider $provider, string $key): mixed
    {
        return (new ReflectionMethod($provider, 'getConfig'))->invoke($provider, $key);
    }

    public function test_entra_derives_the_base_url_from_the_tenant(): void
    {
        $provider = $this->flavor(EntraProvider::class, ['tenant' => 'contoso']);

        $this->assertSame('https://login.microsoftonline.com/contoso/v2.0', $this->configOf($provider, 'base_url'));
    }

    public function test_entra_defaults_to_the_common_tenant(): void
    {
        $provider = $this->flavor(EntraProvider::class, []);

        $this->assertSame('https://login.microsoftonline.com/common/v2.0', $this->configOf($provider, 'base_url'));
    }

    public function test_entra_declares_tenant_as_a_config_key_so_it_survives_the_config_retriever(): void
    {
        $this->assertContains('tenant', EntraProvider::additionalConfigKeys());
    }

    public function test_entra_defaults_to_its_issuer_validator(): void
    {
        $provider = $this->flavor(EntraProvider::class, []);

        $this->assertSame(
            EntraIssuerValidator::class,
            $this->configOf($provider, 'issuer_validator'),
        );

        $pinned = $this->flavor(EntraProvider::class, ['issuer_validator' => 'App\\Custom']);
        $this->assertSame('App\\Custom', $this->configOf($pinned, 'issuer_validator'));
    }

    public function test_entra_defaults_to_preferred_username_for_email(): void
    {
        $provider = $this->flavor(EntraProvider::class, []);

        $this->assertSame(['preferred_username'], $this->configOf($provider, 'email_claims'));

        $pinned = $this->flavor(EntraProvider::class, ['email_claims' => ['preferred_username', 'email']]);
        $this->assertSame(['preferred_username', 'email'], $this->configOf($pinned, 'email_claims'));
    }

    public function test_keycloak_derives_the_base_url_from_server_and_realm(): void
    {
        $provider = $this->flavor(KeycloakProvider::class, [
            'server_url' => 'https://id.example.com/',
            'realm'      => 'main',
        ]);

        $this->assertSame('https://id.example.com/realms/main', $this->configOf($provider, 'base_url'));
    }

    public function test_auth0_derives_the_base_url_and_defaults_to_secret_post(): void
    {
        $provider = $this->flavor(Auth0Provider::class, ['domain' => 'tenant.eu.auth0.com']);

        $this->assertSame('https://tenant.eu.auth0.com', $this->configOf($provider, 'base_url'));
        $this->assertSame('client_secret_post', $this->configOf($provider, 'token_auth_method'));
    }

    public function test_okta_appends_a_custom_authorization_server(): void
    {
        $custom = $this->flavor(OktaProvider::class, [
            'domain'      => 'acme.okta.com',
            'auth_server' => 'default',
        ]);
        $this->assertSame('https://acme.okta.com/oauth2/default', $this->configOf($custom, 'base_url'));

        $org = $this->flavor(OktaProvider::class, ['domain' => 'acme.okta.com']);
        $this->assertSame('https://acme.okta.com', $this->configOf($org, 'base_url'));
    }

    public function test_google_pins_its_issuer(): void
    {
        $provider = $this->flavor(GoogleProvider::class, []);

        $this->assertSame('https://accounts.google.com', $this->configOf($provider, 'base_url'));
    }

    public function test_explicit_config_always_beats_the_derived_defaults(): void
    {
        $provider = $this->flavor(GoogleProvider::class, ['base_url' => 'https://sso.proxy.test']);

        $this->assertSame('https://sso.proxy.test', $this->configOf($provider, 'base_url'));
    }

    public function test_a_custom_subclass_can_derive_whatever_it_needs(): void
    {
        $provider = $this->flavor(TenantPathProvider::class, ['tenant' => 'acme']);

        $this->assertSame('https://sso.mycorp.example/tenants/acme', $this->configOf($provider, 'base_url'));
        $this->assertSame(30, $this->configOf($provider, 'clock_skew'));

        $pinned = $this->flavor(TenantPathProvider::class, ['tenant' => 'acme', 'clock_skew' => 5]);
        $this->assertSame(5, $this->configOf($pinned, 'clock_skew'));
    }

    public function test_the_base_provider_applies_no_defaults(): void
    {
        $provider = $this->flavor(Provider::class, ['base_url' => 'https://op.test']);

        $this->assertSame('https://op.test', $this->configOf($provider, 'base_url'));
        $this->assertNull($this->configOf($provider, 'token_auth_method'));
    }
}
