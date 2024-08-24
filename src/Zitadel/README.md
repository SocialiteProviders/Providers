# Zitadel

```bash
composer require socialiteproviders/zitadel
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'zitadel' => [
  'client_id' => env('ZITADEL_CLIENT_ID'),
  'client_secret' => env('ZITADEL_CLIENT_SECRET'),
  'redirect' => env('ZITADEL_REDIRECT_URI'),
  'base_url' => env('ZITADEL_BASE_URL'),
  'organization_id' => env('ZITADEL_ORGANIZATION_ID'),                      // Optional
  'project_id' => env('ZITADEL_PROJECT_ID'),                                // Optional
  'post_logout_redirect_uri' => env('ZITADEL_POST_LOGOUT_REDIRECT_URI')     // Optional
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

-   Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('zitadel', \SocialiteProviders\Zitadel\Provider::class);
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
        \SocialiteProviders\Zitadel\ZitadelExtendSocialite::class.'@handle',
    ],
];
```

</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('zitadel')->redirect();
```

### Get Logout Url

```php
$idToken = ...; // Retrieve ID token here
return redirect()->away(Socialite::driver('zitadel')->getLogoutUrl($idToken));
```

> [!NOTE]
> Passing the ID token is optional, but it is recommended to logout specific user session.
