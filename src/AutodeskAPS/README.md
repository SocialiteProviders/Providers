# APS

This is a socialite provider for Autodesk APS (formerly Forge). 

```bash
composer require socialiteproviders/autodesk-aps
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

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\APS\AutodeskAPSExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('autodeskaps')->redirect();
```

### Returned user fields

- `id`
- `name`
- `email`
- `username`

### Reference

- [Autodesk documentation](https://aps.autodesk.com/en/docs/oauth/v1/tutorials/get-3-legged-token/);
