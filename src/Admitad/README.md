# Admitad

```bash
composer require socialiteproviders/admitad
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'admitad' => [
    'client_id' => env('ADMITAD_KEY'),
    'client_secret' => env('ADMITAD_SECRET'),
    'redirect' => env('ADMITAD_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Admitad\\AdmitadExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('admitad')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``

### Reference

- [Admitad API Reference](https://account.admitad.com/en/developers/doc/advertiser-api/)
