# Webex

```bash
composer require socialiteproviders/webex
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'webex' => [
  'client_id' => env('WEBEX_CLIENT_ID'),
  'client_secret' => env('WEBEX_CLIENT_SECRET'),
  'redirect' => env('WEBEX_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Webex\WebexExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('webex')->redirect();
```

### Returned User fields

- ``id``
- ``nickname`` (may be `null`)
- ``name`` (may be `null`)
- ``first_name`` (may be `null`)
- ``last_name`` (may be `null`)
- ``email``
- ``avatar`` (may be `null`)
