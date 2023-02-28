# GovBR

```bash
composer require socialiteproviders/govbr
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'govbr' => [
    'client_id' => env('GOVBR_CLIENT_ID'),
    'client_secret' => env('GOVBR_CLIENT_SECRET'),
    'redirect' => env('GOVBR_REDIRECT_URI'),
    'environment' => env('GOVBR_ENVIRONMENT', 'production'),
],
```

### Add provider event listener

Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\GovBR\GovBRExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('govbr')->redirect();
```

### Returned User fields

-   `id`
-   `cpf`
-   `name`
-   `email`
-   `email_verified`
-   `phone_number`
-   `phone_number_verified`
-   `avatar_url`
-   `profile`
