# Kanidm

```bash
composer require socialiteproviders/kanidm
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'kanidm' => [
  'client_id' => env('KANIDM_CLIENT_ID'),
  'client_secret' => env('KANIDM_CLIENT_SECRET'),
  'redirect' => env('KANIDM_REDIRECT_URI'),
  'base_url' => env('KANIDM_BASE_URL'),
],
```

### Add base URL to `.env`

Kanidm may require you to autorize against a custom URL, which you may provide as the base URL.

```bash
KANIDM_BASE_URL=https://idm.example.com/
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Kanidm\KanidmExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('kanidm')->redirect();
```

### Returned User fields

- ``id``
- ``nickname``
- ``name``
- ``email``
