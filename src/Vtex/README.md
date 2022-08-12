# VTEX

```bash
composer require socialiteproviders/vtex
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'vtex' => [    
  'client_id' => env('VTEX_CLIENT_ID'),  
  'client_secret' => env('VTEX_CLIENT_SECRET'),  
  'redirect' => env('VTEX_REDIRECT_URI') ,  
  'storefront_domain' => env('VTEX_STOREFRONT_DOMAIN')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Vtex\VtexExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('vtex')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``