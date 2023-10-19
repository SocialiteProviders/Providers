# Usos

```bash
composer require socialiteproviders/usos
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'usos' => [
  'domain' => env('USOS_DOMAIN_URL'), // Because of every instance of USOS is self-hosted
  'client_id' => env('USOS_CLIENT_ID'),
  'client_secret' => env('USOS_CLIENT_SECRET'),
  'redirect' => env('USOS_REDIRECT_URI')
],
```

As a default, provider loads only necessary for Socialite data. If you need to get more data during authentication process, such as student status or phone number, you may add ```profile_fields_selector``` parameter:

```php
'usos' => [
  // ...
  'profile_fields_selector' => env('USOS_PROFILE_FIELDS_SELECTOR'),
  // ...
]
```

Some fields may be not available without ```scopes``` field specifying.

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\Usos\UsosExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('usos')->redirect();
```

You also can use USOS scopes via Socialite ```scopes``` method:

```php
return Socialite::driver('usos')
    ->scopes(['personal', 'email'])
    ->redirect();
```

To get more information about ```scopes``` you may visit a documentation page at yours USOS instance, or at the [USOSapi Mother server webpage](https://apps.usos.edu.pl/developers/api/authorization/).
