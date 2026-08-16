<?php

namespace SocialiteProviders\OpenIDConnect;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\OpenIDConnect\Providers\Auth0Provider;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;
use SocialiteProviders\OpenIDConnect\Providers\GoogleProvider;
use SocialiteProviders\OpenIDConnect\Providers\KeycloakProvider;
use SocialiteProviders\OpenIDConnect\Providers\OktaProvider;

/**
 * Registers each config/oidc.php connection as its own Socialite driver
 * named "{prefix}{connection}", mirroring its config into services.* where
 * the manager's ConfigRetriever looks it up. A plain services.openidconnect
 * block remains as a single-connection fallback.
 */
class OpenIDConnectServiceProvider extends ServiceProvider
{
    /** @var array<string, class-string<Provider>> */
    protected const PROVIDERS = [
        'entra'    => EntraProvider::class,
        'keycloak' => KeycloakProvider::class,
        'auth0'    => Auth0Provider::class,
        'okta'     => OktaProvider::class,
        'google'   => GoogleProvider::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/oidc.php', 'oidc');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/oidc.php' => $this->app->configPath('oidc.php'),
            ], 'oidc-config');
        }

        foreach ($this->connections() as $driver => [$connection, $providerClass]) {
            config(['services.'.$driver => $connection]);
        }

        Event::listen(SocialiteWasCalled::class, function (SocialiteWasCalled $event) {
            foreach ($this->connections() as $driver => [$connection, $providerClass]) {
                $event->extendSocialite($driver, $providerClass);
            }

            if (($services = config('services.openidconnect')) !== null) {
                $event->extendSocialite('openidconnect', $this->resolveProviderClass(
                    $services['provider'] ?? Provider::class,
                    'openidconnect',
                ));
            }
        });
    }

    /**
     * @return array<string, array{0: array, 1: class-string<Provider>}>
     */
    protected function connections(): array
    {
        $prefix = (string) config('oidc.driver_prefix', 'oidc_');
        $connections = [];

        foreach ((array) config('oidc.connections', []) as $name => $connection) {
            if (! is_array($connection)) {
                continue;
            }

            $providerClass = $this->resolveProviderClass($connection['provider'] ?? Provider::class, $name);

            $connections[$prefix.$name] = [$connection, $providerClass];
        }

        return $connections;
    }

    /**
     * @return class-string<Provider>
     */
    protected function resolveProviderClass(string $provider, string $connection): string
    {
        $class = static::PROVIDERS[strtolower($provider)] ?? $provider;

        if (! is_a($class, Provider::class, true)) {
            throw new InvalidArgumentException(
                'OIDC: connection ['.$connection.'] provider ['.$provider.'] must be one of ['
                .implode(', ', array_keys(static::PROVIDERS)).'] or a class extending '.Provider::class.'.'
            );
        }

        return $class;
    }
}
