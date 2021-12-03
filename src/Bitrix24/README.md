# Bitrix24

```bash
composer require socialiteproviders/bitix24
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'bitix24' => [
      'endpoint' => env('BITRIX24_ENDPOINT'),
      'client_id' => env('BITRIX24_CLIENT_ID'),
      'client_secret' => env('BITRIX24_CLIENT_SECRET'),
      'redirect' => env('BITRIX24_REDIRECT'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Amazon\Bitrix24ExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('bitrix24')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``