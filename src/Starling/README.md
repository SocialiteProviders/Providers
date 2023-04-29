# QuickBooks

```bash
composer require socialiteproviders/starling
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'starling' => [
  'client_id' => env('STARLING_CLIENT_ID'),
  'client_secret' => env('STARLING_CLIENT_SECRET'),
  'redirect' => env('STARLING_REDIRECT_URI'),
  'env' => env('STARLING_ENV'),
  'use_mtls' => env('STARLING_USE_MTLS')
],
```
The `env` value should be `sandbox` for the sandbox environment and `production` for production environment.
The `use_mtls` value should be `true` if you have an OBIE or eIDAS certificate to attach to token API calls.
Add `guzzle` options here to configure the certificates as curl settings.

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Starling\StarlingExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('starling')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``phone``
- ``dateOfBirth``
