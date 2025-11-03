# Authelia

```bash
composer require socialiteproviders/authelia
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Prepare OAuth provider & client in Authelia

Create a new OAuth provider and client within Authelia, according to the Authelia Documentation \
Client: (https://www.authelia.com/configuration/identity-providers/openid-connect/clients/) \
Provider: (https://www.authelia.com/configuration/identity-providers/openid-connect/provider/)


### Add configuration to `config/services.php`

```php
'authelia' => [
  'base_url' => env('AUTHELIA_BASE_URL'),
  'client_id' => env('AUTHELIA_CLIENT_ID'),
  'client_secret' => env('AUTHELIA_CLIENT_SECRET'),
  'redirect' => env('AUTHELIA_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('authelia', \SocialiteProviders\Authelia\Provider::class);
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
        \SocialiteProviders\Authelia\AutheliaExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('authelia')->redirect();
```

### Returned User Fields

`Note`: For types and scope definitions refer https://www.authelia.com/integration/openid-connect/introduction/#scope-definitions \

- email
- email_verified
- alt_emails
- name
- preferred_username
- groups
- id