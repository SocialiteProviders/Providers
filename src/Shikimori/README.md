# Shikimori

```bash
composer require socialiteproviders/shikimori
```
## Register an application 

Add new application at [shikimori.one](https://shikimori.one/oauth/applications).

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'shikimori' => [
  'client_id' => env('SHIKIMORI_CLIENT_ID'),
  'client_secret' => env('SHIKIMORI_CLIENT_SECRET'),
  'redirect' => env('SHIKIMORI_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Shikimori\\ShikimoriExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('shikimori')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``sex``
- ``avatar``

### Reference

- [Shikimori API doc](https://shikimori.one/api/doc)