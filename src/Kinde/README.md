# Kinde

This is a provider for [Kinde](https://kinde.com/)

```bash
composer require socialiteproviders/kinde
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'kinde' => [
  'domain' => env('KINDE_DOMAIN'),
  'client_id' => env('KINDE_CLIENT_ID'),
  'client_secret' => env('KINDE_CLIENT_SECRET'),
  'redirect' => env('KINDE_CALLBACK_URL'),
],
```

### Add domain to `.env`

Kinde provides a customer URL for your different projects. For this reason you need to provide a `domain`.

```bash
KINDE_DOMAIN=https://example.kinde.com
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('kinde', \SocialiteProviders\Kinde\Provider::class);
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
        \SocialiteProviders\Kinde\KindeExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('kinde')->redirect();
```

### Returned User fields

-   `id`
-   `nickname`
-   `name`
-   `email`
-   `avatar`

### Kinde specific fields

If you need to access the `org_code` or `permissions` fields, you can retrieve those from the `raw` user array:

```php
$user = Socialite::driver('kinde')->user();

$rawUser = $user->getRaw();

$orgCode = $rawUser['org_code'];
$permissions = $rawUser['permissions'];
```
