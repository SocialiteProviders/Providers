# Ufutx

```bash
composer require socialiteproviders/ufutx
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'ufutx' => [    
  'client_id' => env('UFUTX_CLIENT_ID'),  
  'client_secret' => env('UFUTX_CLIENT_SECRET'),  
  'redirect' => env('UFUTX_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Ufutx\\UfutxExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('ufutx')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
