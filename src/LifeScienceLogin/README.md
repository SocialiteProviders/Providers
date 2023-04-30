# Life Science Login

```bash
composer require socialiteproviders/lifesciencelogin
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'lifesciencelogin' => [    
  'client_id' => env('LSLOGIN_CLIENT_ID'),  
  'client_secret' => env('LSLOGIN_CLIENT_SECRET'),  
  'redirect' => env('LSLOGIN_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\LifeScienceLogin\LifeScienceLoginExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('lifesciencelogin')->redirect();
```

### Returned User Fields

- ``id``
- ``name``
- ``given_name``
- ``family_name``
- ``email``
