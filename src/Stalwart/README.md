---
category: Productivity / Business
---

# Stalwart

```bash
composer require socialiteproviders/stalwart
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Register an OAuth Client in Stalwart

1. Log into your Stalwart Admin Web Console (`https://mail-admin.example.com`).
2. Navigate to **Directory / Settings -> OAuth / OIDC Clients**.
3. Create a new client and configure your `client_id`, `client_secret`, and authorized `redirect_uris`.
4. For detailed documentation on Stalwart OAuth 2.0 and OpenID Connect settings, see the [Stalwart OpenID Connect Documentation](https://stalw.art/docs/configure/auth/openid-connect/).

### Add configuration to `config/services.php`

```php
'stalwart' => [
  'client_id' => env('STALWART_CLIENT_ID'),
  'client_secret' => env('STALWART_CLIENT_SECRET'),
  'redirect' => env('STALWART_REDIRECT_URI'),
  'base_url' => env('STALWART_BASE_URL'), // e.g. https://mail.example.com
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('stalwart', \SocialiteProviders\Stalwart\Provider::class);
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
        \SocialiteProviders\Stalwart\StalwartExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('stalwart')->redirect();
```

### Returned User fields

- `id` (sub)
- `nickname` (preferred_username)
- `name`
- `email`
- `avatar` (picture)
