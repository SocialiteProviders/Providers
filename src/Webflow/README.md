# Webflow

```bash
composer require socialiteproviders/webflow
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'webflow' => [
  'client_id' => env('WEBFLOW_CLIENT_ID'),
  'client_secret' => env('WEBFLOW_CLIENT_SECRET'),
  'redirect' => env('WEBFLOW_REDIRECT_URI')
],
```

See the [Webflow Developer Docs](https://developers.webflow.com/docs/oauth) for how to register an app and obtain these details.

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Webflow\WebflowExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('webflow')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
