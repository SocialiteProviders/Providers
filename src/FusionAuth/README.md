# FusionAuth

```bash
composer require socialiteproviders/fusionauth
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'fusionauth' => [
    'client_id' => env('FUSIONAUTH_CLIENT_ID'),
    'client_secret' => env('FUSIONAUTH_CLIENT_SECRET'),
    'redirect' => env('FUSIONAUTH_REDIRECT_URI'),
    'base_url' => env('FUSIONAUTH_BASE_URL'), // Base URL of your cloud instance or self hosted instance
    'tenant_id' => env('FUSIONAUTH_TENANT_ID'), // Tenant ID of the client. Required since FusionAuth 1.8.0
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        'SocialiteProviders\\FusionAuth\\FusionAuthExtendSocialite@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('fusionauth')->redirect();
```
