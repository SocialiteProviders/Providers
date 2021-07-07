# Gumroad

```bash
composer require socialiteproviders/gumroad
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'gumroad' => [    
  'client_id' => env('GUMROAD_CLIENT_ID'),  
  'client_secret' => env('GUMROAD_CLIENT_SECRET'),  
  'redirect' => env('GUMROAD_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Gumroad\\GumroadExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('gumroad')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
