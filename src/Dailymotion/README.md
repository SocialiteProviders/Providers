# Dailymotion

```bash
composer require socialiteproviders/dailymotion
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'dailymotion' => [    
  'client_id' => env('DAILYMOTION_CLIENT_ID'),  
  'client_secret' => env('DAILYMOTION_CLIENT_SECRET'),  
  'redirect' => env('DAILYMOTION_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Dailymotion\DailymotionExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('dailymotion')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
