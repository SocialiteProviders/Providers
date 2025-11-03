# QuickBooks

```bash
composer require socialiteproviders/starling
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'starling' => [
  'client_id' => env('STARLING_CLIENT_ID'),
  'client_secret' => env('STARLING_CLIENT_SECRET'),
  'redirect' => env('STARLING_REDIRECT_URI'),
  'env' => env('STARLING_ENV'),
  'use_mtls' => env('STARLING_USE_MTLS')
],
```
The `env` value should be `sandbox` for the sandbox environment and `production` for production environment.
The `use_mtls` value should be `true` if you have an OBIE or eIDAS certificate to attach to token API calls.
Add `guzzle` options here to configure the certificates as curl settings.

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('starling', \SocialiteProviders\Starling\Provider::class);
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
        \SocialiteProviders\Starling\StarlingExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('starling')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``phone``
- ``dateOfBirth``
