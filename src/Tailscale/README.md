# Tailscale (tsidp)

```bash
composer require socialiteproviders/tailscale
```

## Prerequisites

Install [Tailscale OpenID Connect (OIDC) Identity Provider (tsidp)](https://github.com/tailscale/tsidp) and make it available on your Tailscale network.

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'tailscale' => [
  'base_url' => env('TAILSCALE_BASE_URL'),
  'client_id' => env('TAILSCALE_CLIENT_ID'),
  'client_secret' => env('TAILSCALE_CLIENT_SECRET'),
  'redirect' => env('TAILSCALE_REDIRECT_URI'),
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('tailscale', \SocialiteProviders\Tailscale\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Tailscale\TailscaleExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('tailscale')->redirect();
```

### Returned User fields

- ``id``
- ``email``
- ``name``
- ``username`` (same as `name`)
