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

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('govbr', \SocialiteProviders\GovBR\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
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
</details>

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
