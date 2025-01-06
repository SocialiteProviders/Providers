# Authentik

```bash
composer require socialiteproviders/authentik
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/),
then follow the provider specific instructions below.

### Prepare OAuth provider & application in Authentik

Create a new OAuth provider within Authentik, according to the Authentik
Documentation (https://goauthentik.io/docs/providers/oauth2/)

### Add configuration to `config/services.php`

```php
'authentik' => [
  'base_url' => env('AUTHENTIK_BASE_URL'),
  'client_id' => env('AUTHENTIK_CLIENT_ID'),
  'client_secret' => env('AUTHENTIK_CLIENT_SECRET'),
  'redirect' => env('AUTHENTIK_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('authentik', \SocialiteProviders\Authentik\Provider::class);
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
        \SocialiteProviders\Authentik\AuthentikExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use
Socialite (assuming you have the facade installed):

```php
return Socialite::driver('authentik')->redirect();
```

To redirect to the authentication, and then:

```php
$user = Socialite::driver('authentik')->user()
```

In the return function. The user will contain a `name` and `email` field
populated from the OAuth source.
