# Etsy

Support for OpenAPI v3 on Etsy.

Note: V2 is scheduled to sunset Q4 2022.

## Installation & Basic Usage

```bash
composer require socialiteproviders/etsy
```

### Add configuration to `config/services.php`

```php
'etsy' => [    
  'client_id' => env('ETSY_CLIENT_ID'),  
  'client_secret' => env('ETSY_CLIENT_SECRET'),  
  'redirect' => env('ETSY_REDIRECT_URI') 
],
```

### Add variables to `.env`
You can find/update this information from https://www.etsy.com/developers/your-apps
```
ETSY_CLIENT_ID={YOUR API KEY}
ETSY_CLIENT_SECRET={YOUR SECRET}
ETSY_REDIRECT_URI=https://example.com/callback
```

### Add provider event listener `app/Providers/EventServiceProvider`

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Etsy\EtsyExtendSocialite::class.'@handle',
    ],
];
```

### Usage `web/routes.php`

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed).

Note: The `email_r` is enabled by default so you can access user information in the callback. 

```php
// the redirect
return Socialite::driver('etsy')
        ->scopes[['include','scopes','here']]  
        ->redirect();

// the callback
$etsyUser = Socialite::driver('etsy')
        ->user();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
