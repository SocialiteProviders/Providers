# Mediawiki

```bash
composer require socialiteproviders/mediawiki
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'mediawiki' => [
  'client_id' => env('MEDIAWIKI_CLIENT_ID'),
  'client_secret' => env('MEDIAWIKI_CLIENT_SECRET'),
  'redirect' => env('MEDIAWIKI_REDIRECT_URI'),
  'base_url' => env('MEDIAWIKI_BASE_URL'),
],
```

### Add base URL to `.env`

Mediawiki may require you to autorize against a custom URL, which you may provide as the base URL.

```bash
MEDIAWIKI_BASE_URL=https://meta.wikimedia.org/w/rest.php
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Mediawiki\MediawikiExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('mediawiki')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
