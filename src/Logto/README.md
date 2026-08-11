---
category: Social / Platform
---

# Logto

```bash
composer require socialiteproviders/logto
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/),
then follow the provider specific instructions below.

### Prepare OAuth application in Logto

Create a **Traditional Web** application in your Logto Console
(https://docs.logto.io/integrate-logto/traditional-web) and set its redirect URI
to your app's callback (e.g. `https://example.com/auth/logto/callback`).

### Add configuration to `config/services.php`

```php
'logto' => [
  'base_url' => env('LOGTO_BASE_URL'), // e.g. https://auth.example.com or https://<tenant>.logto.app
  'client_id' => env('LOGTO_CLIENT_ID'),
  'client_secret' => env('LOGTO_CLIENT_SECRET'),
  'redirect' => env('LOGTO_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('logto', \SocialiteProviders\Logto\Provider::class);
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
        \SocialiteProviders\Logto\LogtoExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use
Socialite (assuming you have the facade installed):

```php
return Socialite::driver('logto')->redirect();
```

To redirect to the authentication, and then:

```php
$user = Socialite::driver('logto')->user();
```

In the return function. The user will contain a `name` and `email` field
populated from the Logto `/oidc/me` endpoint, along with `id` (`sub`),
`nickname`/`preferred_username` (`username`) and `avatar` (`picture`).
