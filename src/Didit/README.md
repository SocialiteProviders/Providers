<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://docs.didit.me/_next/static/media/didit-logo-wordmark-white.3ddb2264.svg#gh-dark-mode-only">
  <img height="250px" alt="Didit Logo" src="https://docs.didit.me/_next/static/media/didit-logo-wordmark-black.3479c043.svg#gh-light-mode-only">
</picture>

# Didit

```bash
composer require socialiteproviders/didit
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'didit' => [
  'client_id' => env('DIDIT_CLIENT_ID'),
  'client_secret' => env('DIDIT_CLIENT_SECRET'),
  'redirect' => env('DIDIT_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('didit', \SocialiteProviders\Didit\Provider::class);
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
        \SocialiteProviders\Didit\DiditExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('didit')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``avatar``
