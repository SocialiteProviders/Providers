# PropelAuth

This is a provider for [PropelAuth](https://propelauth.com/)

```bash
composer require socialiteproviders/propelauth
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the [docs here](https://docs.propelauth.com/overview/authentication/oauth2) on how to setup an OAuth provider in PropelAuth.

### Add configuration to `config/services.php`

```php
'propelauth' => [
  'client_id' => env('PROPELAUTH_CLIENT_ID'),
  'client_secret' => env('PROPELAUTH_CLIENT_SECRET'),
  'redirect' => env('PROPELAUTH_CALLBACK_URL'),
  'auth_url' => env('PROPELAUTH_AUTH_URL'),
],
```

### Add Auth URL to `.env`

Get your Auth URL from PropelAuth in the Frontend Integration page. The `PROPELAUTH_CALLBACK_URL` value must be entered as a **Redirect URI** in PropelAuth.

```bash
PROPELAUTH_AUTH_URL=https://example.propelauth.com
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('propelauth', \SocialiteProviders\PropelAuth\Provider::class);
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
        \SocialiteProviders\PropelAuth\PropelAuthExtendSocialite::class.'@handle',
    ],
];
```

</details>

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('propelauth')->redirect();
```

### Returned User fields

- `id`
- `email`
- `first_name`
- `last_name`

### Additional PropelAuth Fields

If you need to access additional user fields, you can retrieve those from the `raw` user array:

```php
$user = Socialite::driver('propelauth')->user();

$rawUser = $user->getRaw();

$orgs = $rawUser['org_id_to_org_info'];
```
