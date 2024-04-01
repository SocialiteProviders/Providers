# Auth0

```bash
composer require socialiteproviders/auth0
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'auth0' => [
  'client_id' => env('AUTH0_CLIENT_ID'),
  'client_secret' => env('AUTH0_CLIENT_SECRET'),
  'redirect' => env('AUTH0_REDIRECT_URI'),
  'base_url' => env('AUTH0_BASE_URL'),
],
```

### Add base URL to `.env`

Auth0 may require you to autorize against a custom URL, which you may provide as the base URL.

```bash
AUTH0_BASE_URL=https://example.auth0.com/
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('auth0', \SocialiteProviders\Auth0\Provider::class);
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
        \SocialiteProviders\Auth0\Auth0ExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('auth0')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
