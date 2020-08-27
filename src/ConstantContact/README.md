# ConstantContact

```bash
composer require socialiteproviders/constantcontact
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'constantcontact' => [    
  'client_id' => env('CONSTANTCONTACT_CLIENT_ID'),  
  'client_secret' => env('CONSTANTCONTACT_CLIENT_SECRET'),  
  'redirect' => env('CONSTANTCONTACT_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\ConstantContact\\ConstantContactExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('constantcontact')->redirect();
```

### Returned User fields

- ``name``
- ``email``
