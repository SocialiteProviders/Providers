# Zettle

Zettle by Paypal is a point of sale payment provider. [API documentation](https://developer.zettle.com/docs/api)

```bash
composer require socialiteproviders/zettle
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'zettle' => [
  'client_id' => env('ZETTLE_CLIENT_ID'),
  'client_secret' => env('ZETTLE_CLIENT_SECRET'),
  'redirect' => env('ZETTLE_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('zettle', \SocialiteProviders\Zettle\Provider::class);
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
        \SocialiteProviders\Zettle\ZettleExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('zettle')->redirect();
```

Note that the Zettle API does not return any user information except a user ID and organization ID, both in the form of UUIDs.

```php
$user = Socialite::driver('zettle')->user();
$user->id; // 6c2828b9-c939-4713-8aad-17e1ef68bc96
$user->user["organizationUuid"]; // 138a9091-a154-4cf5-a32a-55d8a76b8e32
```

You can delete an app connection by calling the `disconnect` method with an access token:

```php
Socialite::driver('zettle')->disconnect($accessToken);
```
