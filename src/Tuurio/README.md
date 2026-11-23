---
category: Social / Platform
name: Tuurio ID
---

# Tuurio ID

```bash
composer require socialiteproviders/tuurio
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Prepare a client in Tuurio ID

[Tuurio ID](https://id.tuurio.com) is a hosted OpenID Connect provider. Every tenant is its own issuer, so the provider needs the base URL of your tenant. Create a client in your tenant and register your app's callback (e.g. `https://example.com/auth/tuurio/callback`) as its redirect URI; redirect URIs must match exactly. See the [Tuurio ID developer docs](https://id.tuurio.com/en/public/developers).

Tuurio ID requires PKCE (S256) for every client, so this provider always sends a code challenge. It needs a session to carry the code verifier.

### Add configuration to `config/services.php`

```php
'tuurio' => [
  'client_id' => env('TUURIO_CLIENT_ID'),
  'client_secret' => env('TUURIO_CLIENT_SECRET'),
  'redirect' => env('TUURIO_REDIRECT_URI'),
  'base_url' => env('TUURIO_BASE_URL'), // e.g. https://<tenant>.id.tuurio.com
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('tuurio', \SocialiteProviders\Tuurio\Provider::class);
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
        \SocialiteProviders\Tuurio\TuurioExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('tuurio')->redirect();
```

### Returned User fields

- `id` (the `sub` claim)
- `nickname` (the `preferred_username` claim)
- `name`
- `email`
- `email_verified`
- `locale`

### Validating the id_token

This provider reads the user from the userinfo endpoint and does not validate the id_token. If you need the id_token validated (signature, `iss`, `aud`, `nonce`) or back-channel logout, use the [OpenID Connect provider](https://socialiteproviders.com/OpenIDConnect/) and set its `base_url` to your tenant.
