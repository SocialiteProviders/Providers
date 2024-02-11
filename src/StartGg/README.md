# Start.gg

```bash
composer require socialiteproviders/startgg
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'startgg' => [    
  'client_id' => env('STARTGG_CLIENT_ID'),  
  'client_secret' => env('STARTGG_CLIENT_SECRET'),  
  'redirect' => env('STARTGG_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\StartGg\StartGgExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('startgg')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
- ``discriminator`` (Uniquely identifying token for user)

### Reference

- [Start.gg Developer Portal](https://dev.start.gg/docs/oauth/oauth-overview)
