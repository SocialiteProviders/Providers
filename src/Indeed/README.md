# Discord

```bash
composer require socialiteproviders/indeed
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'indeed' => [    
  'client_id' => env('INDEED_CLIENT_ID'),  
  'client_secret' => env('INDEED_CLIENT_SECRET'),  
  'redirect' => env('INDEED_REDIRECT_URI'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Indeed\IndeedExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('indeed')->redirect();
```

### Returned User fields

- ``sub`` (**string**) Unique identifier for the user's account. e.g. `248289761001`
- ``email`` (**string**) User's email address.
- ``email_verified`` (**boolean**) Indicates whether the user has verified their email address. e.g. `true`