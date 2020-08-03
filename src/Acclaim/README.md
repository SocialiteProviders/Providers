# Acclaim

```bash
composer require socialiteproviders/acclaim
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`.

```php
'acclaim' => [
    'client_id' => env('ACCLAIM_KEY'),
    'client_secret' => env('ACCLAIM_SECRET'),
    'redirect' => env('ACCLAIM_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to the listen for `SocialiteWasCalled` events. 

Add the event to your `listen[]` array  in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Acclaim\\AcclaimExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::with('acclaim')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name`` (same as ``nickname``)
- ``email``
- ``avatar``
