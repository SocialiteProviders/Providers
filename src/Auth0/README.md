# Auth0

```bash
composer require socialiteproviders/auth0
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'auth0' => [    
  'client_id' => env('AUTH0_CLIENT_ID'),  
  'client_secret' => env('AUTH0_CLIENT_SECRET'),  
  'redirect' => env('AUTH0_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Auth0\\Auth0ExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('auth0')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
