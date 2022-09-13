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
However, there are a few things to note with the OpenApi v3 on Etsy. They require the use of PKCE. They also require that 
`x-api-key` with your API Key be included in thea header for each request. The header code is taken care of in this provider.
But you will need to call `enablePKCE` on your requests as well as add any scopes you want to access. The `email_r` is enabled
by default so you can access user information in the callback.

```php
// the redirect
return Socialite::driver('etsy')
        ->scopes[['include','scopes','here']]
        ->enablePKCE()
        ->redirect();

// the callback
$etsyUser = Socialite::driver('etsy')
        ->enablePKCE()
        ->user();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
- ``avatar``
