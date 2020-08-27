# Zoho

```bash
composer require socialiteproviders/zoho
```

## Register an application 

Add new application at [zoho.com](https://accounts.zoho.com/developerconsole).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'zoho' => [    
  'client_id' => env('ZOHO_CLIENT_ID'),  
  'client_secret' => env('ZOHO_CLIENT_SECRET'),  
  'redirect' => env('ZOHO_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Zoho\\ZohoExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('zoho')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``avatar``

### Reference

- [Zoho API Reference](https://www.zoho.com/developer/rest-api.html)
