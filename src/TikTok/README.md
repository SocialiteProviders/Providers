# TikTok

```bash
composer require socialiteproviders/tiktok
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'tiktok' => [
  'client_id' => env('TIKTOK_CLIENT_ID'),
  'client_secret' => env('TIKTOK_CLIENT_SECRET'),
  'redirect' => env('TIKTOK_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('tiktok', \SocialiteProviders\TikTok\Provider::class);
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
        \SocialiteProviders\TikTok\TikTokExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('tiktok')->redirect();
```

### Important information
For proper operation make sure you have the following permissions/scopes approved:
 - `user.basic.info`*
 - `user.info.profile` (optional and recommended, required for `username`)
 - `user.info.stats`(optional and recommended)

# Returned User Fields

- id
- username (requires the permission/scope `user.info.profile`)
- union_id
- name
- avatar

# Reference

- [TikTok Login Kit](https://developers.tiktok.com/doc/login-kit-web)
