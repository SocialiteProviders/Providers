# Apple

```bash
composer require socialiteproviders/apple
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'apple' => [
  'client_id' => env('APPLE_CLIENT_ID'),
  'client_secret' => env('APPLE_CLIENT_SECRET'),
  'redirect' => env('APPLE_REDIRECT_URI')
],
```

See [Configure Apple ID Authentication](https://developer.okta.com/blog/2019/06/04/what-the-heck-is-sign-in-with-apple)

> Note: the client secret used for "Sign In with Apple" is a JWT token that can have a maximum lifetime of 6 months. The article above explains how to generate the client secret on demand and you'll need to update this every 6 months. To generate the client secret for each request, see [Generating A Client Secret For Sign In With Apple On Each Request](https://bannister.me/blog/generating-a-client-secret-for-sign-in-with-apple-on-each-request)

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
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
        \SocialiteProviders\Apple\AppleExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('apple')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``

### Reference

- [Apple API Reference](https://developer.apple.com/documentation/signinwithapplerestapi/)
