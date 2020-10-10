# Reddit

```bash
composer require socialiteproviders/rekono
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'rekono' => [    
    'client_id' => env('REKONO_CLIENT_ID'),  
    'client_secret' => env('REKONO_CLIENT_SECRET'),  
    'redirect' => env('REKONO_REDIRECT_URI') 
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Rekono\\RekonoExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('rekono')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``

More fields are available under the user subkey:

```php
$user = Socialite::driver('rekono')->user();

$locale = $user->user['locale'];
$email_verified = $user->user['email_verified'];
```