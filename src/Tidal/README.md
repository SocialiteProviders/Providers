---
category: Music
name: TIDAL
---

# TIDAL

```bash
composer require socialiteproviders/tidal
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

Create an app in the [TIDAL Developer Dashboard](https://developer.tidal.com/dashboard) and add your callback URL as a redirect URI. TIDAL uses the [Authorization Code flow with PKCE](https://developer.tidal.com/documentation/api-sdk/api-sdk-authorization), which this provider enables for you. The [TIDAL API reference](https://tidal-music.github.io/tidal-api-reference/) documents the endpoints and scopes.

### Add configuration to `config/services.php`

```php
'tidal' => [
  'client_id' => env('TIDAL_CLIENT_ID'),
  'client_secret' => env('TIDAL_CLIENT_SECRET'),
  'redirect' => env('TIDAL_REDIRECT_URI'),
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('tidal', \SocialiteProviders\Tidal\Provider::class);
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
        \SocialiteProviders\Tidal\TidalExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('tidal')->redirect();
```

### Returned User fields

The `user.read` scope is required for these fields, so it is always requested, even if you replace the default scopes with `setScopes()`. Use `scopes()` to request additional ones.

- `id`
- `nickname` (TIDAL username, which is often the account email)
- `name` (first and last name, when TIDAL returns them, otherwise `null`)
- `email`

The full [JSON:API user document](https://tidal-music.github.io/tidal-api-reference/) (including `country` and `emailVerified`) is available via `$user->getRaw()`.
