# HarID

```bash
composer require socialiteproviders/harid
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'harid' => [
  'client_id' => env('HARID_CLIENT_ID'),
  'client_secret' => env('HARID_CLIENT_SECRET'),
  'redirect' => env('HARID_REDIRECT_URI'),
  'use_test_idp' => false,
],
```

Please note that `use_test_idp` could be omitted and would default to `false`.

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('harid', \SocialiteProviders\HarID\Provider::class);
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
        \SocialiteProviders\HarID\HarIDExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('harid')->redirect();
```

Default scopes are set to `openid profile email session_type`. Additional scopes could be `personal_code`, `roles` and `custodies`.
Please see the [HarID documentation](https://harid.ee/en/pages/dev-info) for specifications. If you really want to add
some additional scopes or replace the default ones, then please read the [documentation](https://laravel.com/docs/8.x/socialite#access-scopes).

### Returned User fields

- ``id``
- ``nickname`` - will have the value of `sub` and should probably be kept secret
- ``name``
- ``email``
- ``avatar`` - will be set to an empty string because `HarID` does not provide any images

#### Additional HarID specific data

- ``given_name``
- ``family_name``
- ``email_verified``
- ``strong_session``
- ``ui_locales``

Those are stored within the `user` parameter and could be checked for using `offsetExists($name)` and fetched using `offsetGet($name)`.
