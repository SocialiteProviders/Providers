# YNAB (You Need A Budget)

```bash
composer require socialiteproviders/ynab
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

```php
'ynab' => [
    'client_id' => env('YNAB_CLIENT_ID'),
    'client_secret' => env('YNAB_CLIENT_SECRET'),
    'redirect' => env('YNAB_CALLBACK_URL'),
    'scope' => explode(",", env('YNAB_SCOPES'))
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('ynab', \SocialiteProviders\YNAB\Provider::class);
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
        \SocialiteProviders\YNAB\YNABExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('ynab')->redirect();
```

Example env
```php
YNAB_CLIENT_ID=abc123
YNAB_CLIENT_SECRET=abc123
YNAB_CALLBACK_URL=https://your-app.ngrok.io/oauth2/callback
YNAB_LOGIN_SCOPE="read-only"
```

#### Helpful tips
- YNAB only provides the ID of the user, and does not provide the name or email address or other oauth2 attributes.

### Returned User fields

- ``id``
