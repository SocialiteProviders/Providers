---
category: Payments
---

# Steady

```bash
composer require socialiteproviders/steady
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Prepare OAuth application in Steady

Register an application in your Steady backend under **Your Project Settings -> Integration -> API**. Steady issues a client ID and client secret and lets you set the redirect URI, which must match the one configured below exactly.

### Add configuration to `config/services.php`

```php
'steady' => [
  'client_id' => env('STEADY_CLIENT_ID'),
  'client_secret' => env('STEADY_CLIENT_SECRET'),
  'redirect' => env('STEADY_REDIRECT_URI')
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('steady', \SocialiteProviders\Steady\Provider::class);
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
        \SocialiteProviders\Steady\SteadyExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('steady')->redirect();
```

### Returned User fields

- ``id``
- ``name``
- ``email``
- ``avatar``
- ``first_name``
- ``last_name``

``nickname`` is always ``null``; Steady does not expose usernames.

### Reading the user's subscription

Steady's whole purpose is memberships, so the provider exposes the subscription
the user holds for your publication. Pass the access token you already received
from `user()`:

```php
$user = Socialite::driver('steady')->user();

$subscription = Socialite::driver('steady')->getSubscriptionByToken($user->token);

$state = $subscription['attributes']['state'] ?? null; // e.g. "active"
```

The method returns `null` when the user has no subscription. Useful attributes
include `state`, `period`, `currency`, `monthly-amount`, `expires-at`,
`rss-feed-url`, `is-gift` and `shipping-address`.

### Refreshing the access token

Steady access tokens expire after seven days, refresh tokens after a year, so
store `$user->refreshToken` alongside the access token and exchange it with
Socialite's built-in refresh:

```php
$token = Socialite::driver('steady')->refreshToken($refreshToken);

$token->token;        // the new access token
$token->refreshToken; // rotate the stored refresh token
$token->expiresIn;
```
