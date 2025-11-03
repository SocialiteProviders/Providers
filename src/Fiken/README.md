# Fiken

Fiken.no is a Norwegian online accounting system. [API documentation](https://api.fiken.no/api/v2/docs/)

```bash
composer require socialiteproviders/fiken
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'fiken' => [
  'client_id' => env('FIKEN_CLIENT_ID'),
  'client_secret' => env('FIKEN_CLIENT_SECRET'),
  'redirect' => env('FIKEN_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('fiken', \SocialiteProviders\Fiken\Provider::class);
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
        \SocialiteProviders\Fiken\FikenExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('fiken')->redirect();
```

You can revoke a token by calling the `revokeToken` method with an access token:

```php
Socialite::driver('fiken')->revokeToken($accessToken);
```
