# SapoId

```bash
composer require socialiteproviders/sapoid
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'sapoid' => [
  'client_id' => env('SAPOID_CLIENT_ID'),
  'client_secret' => env('SAPOID_CLIENT_SECRET'),
  'redirect' => env('SAPOID_REDIRECT_URI'),
  // optional, depending on the extra scopes you've configured in your app
  'scopes' => ['profile', 'email'],
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('sapoid', \SocialiteProviders\SapoId\Provider::class);
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
        \SocialiteProviders\SapoId\SapoIdExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Configure App in SAPO ID
To configure your app in SAPO ID, you need to create an app in the [SAPO ID Connect](https://id.sapo.pt/connect) and get the client ID and client secret.

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('sapoid')->redirect();
```
