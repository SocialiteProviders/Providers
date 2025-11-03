# APS

This is a socialite provider for Autodesk APS (formerly Forge). 

```bash
composer require socialiteproviders/aps
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
// Autodesk APS (Forge)
'autodesk_aps' => [    
  'client_id' => env('AUTODESK_APS_CLIENT_ID'),  
  'client_secret' => env('AUTODESK_APS_CLIENT_SECRET'),  
  'redirect' => env('AUTODESK_APS_REDIRECT_URI'),
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('autodeskaps', \SocialiteProviders\AutodeskAPS\Provider::class);
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
        \SocialiteProviders\AutodeskAPS\AutodeskAPSExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('autodeskaps')->redirect();
```

### Returned user fields

Make sure to ask for the proper [scopes](https://aps.autodesk.com/en/docs/oauth/v2/developers_guide/scopes/).
- `id`
- `email`
- `email_verified`
- `username`
- `full_name`
- `first_name`
- `last_name`
- `language`
- `image`
- `website`

### Reference

- [Autodesk documentation](https://aps.autodesk.com/en/docs/oauth/v2/developers_guide/overview/);
