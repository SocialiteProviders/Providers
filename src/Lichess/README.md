# Lichess

```bash
composer require socialiteproviders/lichess
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'lichess' => [
  'client_id' => env('LICHESS_CLIENT_ID'),
  'client_secret' => env('LICHESS_CLIENT_SECRET'),
  'redirect' => env('LICHESS_REDIRECT_URI')
],
```

According to Lichess.org API reference (2.0.0) Lichess supports unregistered and public clients (no client authentication, choose any unique LICHESS_CLIENT_ID). LICHESS_CLIENT_SECRET is not needed and can be empty. Access tokens are long-lived (expect one year), unless they are revoked. Refresh tokens are not supported.

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Lichess\\LichessExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('lichess')->redirect();
```

### Returned User fields

- ``id``
- ``username``
- ``email``

