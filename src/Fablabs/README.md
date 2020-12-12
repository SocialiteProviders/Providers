# Fablabs

```bash
composer require socialiteproviders/fablabs
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'fablabs' => [
  'client_id' => env('FABLABS_CLIENT_ID'),
  'client_secret' => env('FABLABS_CLIENT_SECRET'),
  'redirect' => env('FABLABS_REDIRECT_URI')
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\Fablabs\\FablabsExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('fablabs')->redirect();
```

### Returned User fields

- `id`
- `nickname`
- `email`
- `name`

### Reference

- [Fablabs.io](https://fablabs.io/);
- [OAuth authorization Docs](https://docs.fablabs.io/);
