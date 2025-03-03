# Immutable X OAuth2 Provider for Laravel Socialite

[![Latest Stable Version](https://poser.pugx.org/socialiteproviders/immutablex/v/stable)](https://packagist.org/packages/socialiteproviders/immutablex)
[![Total Downloads](https://poser.pugx.org/socialiteproviders/immutablex/downloads)](https://packagist.org/packages/socialiteproviders/immutablex)
[![License](https://poser.pugx.org/socialiteproviders/immutablex/license)](https://packagist.org/packages/socialiteproviders/immutablex)

## 🛠️ Installation

To install the Immutable X Socialite provider, run:

```bash
composer require socialiteproviders/immutablex


## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'immutablex' => [
    'client_id'     => env('IMX_CLIENT_ID'),
    'client_secret' => env('IMX_CLIENT_SECRET'),
    'redirect'      => env('IMX_REDIRECT_URI'),
],
```

### Add provider event listener

### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('immutable', \SocialiteProviders\ImmutableX\Provider::class);
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
        \SocialiteProviders\Immutable\ImmutableXExtendSocialite::class.'@handle',
    ],
];
```
</details>

### 🚀 Usage

Redirecting the User

To initiate the Immutable X login flow, use:


```php
return Socialite::driver('immutablex')->redirect();
```


Handling the Callback

After the user logs in, Immutable X will redirect them back to your application. Retrieve their information using:


``` php

$user = Socialite::driver('immutablex')->user();

$user->getId();       // Immutable X unique user ID
$user->getEmail();    // User's email address
$user->token;         // OAuth access token

```


Stateless Mode (For APIs)

If you’re using an API-based authentication and don’t use sessions, ensure that you call stateless() before retrieving the user:

``` php

$user = Socialite::driver('immutablex')->stateless()->user();


```

### 🔄 Returned User Fields

- ``sub`` (**string**) Unique identifier for the user's account. e.g. `248289761001`
- ``email`` (**string**) User's email address.
- ``token`` (**string**) OAuth access token.

### 🔬 Testing

```bash
vendor/bin/phpunit tests
```

### 📜 License

This package is open-source and released under the MIT License. See the LICENSE file for details.

### 🎯 Need Help?

For questions and support, open an issue in the GitHub repository.
