# Box

```bash
composer require socialiteproviders/box
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'box' => [    
  'client_id' => env('BOX_CLIENT_ID'),  
  'client_secret' => env('BOX_CLIENT_SECRET'),  
  'redirect' => env('BOX_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Box\\BoxExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('box')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``avatar``

### Reference

- [Box API Reference](https://developer.box.com/reference/)
